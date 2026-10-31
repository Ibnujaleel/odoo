<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillingPeriod;
use App\Enums\DiscountAppliesTo;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\DiscountUsage;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generates recurring invoices for active subscriptions.
 *
 * Idempotency guarantee:
 *   Uses firstOrCreate on the UNIQUE(subscription_id, billing_period_start)
 *   constraint. Calling generateFor() twice for the same period returns the
 *   existing invoice without side-effects.
 *
 * Side-effects on successful creation:
 *   - Copies subscription lines → invoice lines (with tax and discount calculations)
 *   - Advances subscription.next_billing_date by the plan's billing_period
 *   - Records discount usages and increments times_used
 *   - Auto-closes subscription if plan.auto_close and plan.end_date has passed
 */
class BillingService
{
    public function __construct(
        protected TaxCalculator $taxCalculator,
        protected DiscountEngine $discountEngine,
    ) {}

    /**
     * Generate an invoice for the subscription's current billing period.
     *
     * @param  Subscription  $subscription  Must have plan, lines, and lines.tax loaded.
     * @return Invoice|null                 The invoice, or null if the subscription was skipped.
     */
    public function generateFor(Subscription $subscription): ?Invoice
    {
        $status = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status->value
            : (string) $subscription->status;

        // Guard: only bill active subscriptions
        if ($status !== 'active') {
            return null;
        }

        // Guard: not yet due
        if ($subscription->next_billing_date > Carbon::today()) {
            return null;
        }

        $plan = $subscription->plan;

        // Auto-close check: if the plan has auto_close and end_date has passed,
        // close the subscription instead of billing.
        if ($plan && $plan->auto_close && $plan->end_date && Carbon::parse($plan->end_date)->lt(Carbon::today())) {
            app(SubscriptionStateMachine::class)->transition($subscription, 'closed');

            return null;
        }

        $period = $plan?->billing_period instanceof BillingPeriod
            ? $plan->billing_period->value
            : (string) ($plan?->billing_period ?? 'monthly');

        $billingPeriodStart = Carbon::parse($subscription->next_billing_date);
        $billingPeriodEnd   = $this->advanceDate($billingPeriodStart, $period)->subDay();

        return DB::transaction(function () use ($subscription, $plan, $period, $billingPeriodStart, $billingPeriodEnd) {
            // Idempotent: firstOrCreate on the unique constraint
            $invoice = Invoice::firstOrCreate(
                [
                    'subscription_id'      => $subscription->id,
                    'billing_period_start' => $billingPeriodStart->toDateString(),
                ],
                [
                    'number'               => $this->generateInvoiceNumber(),
                    'customer_id'          => $subscription->customer_id,
                    'billing_period_end'   => $billingPeriodEnd->toDateString(),
                    'status'               => InvoiceStatus::Draft->value,
                    'subtotal'             => 0,
                    'discount_total'       => 0,
                    'tax_total'            => 0,
                    'total'                => 0,
                    'amount_paid'          => 0,
                    'due_date'             => $billingPeriodStart->copy()->addDays(
                        $subscription->payment_terms ?? 30
                    )->toDateString(),
                ]
            );

            // If it already existed, return it without further side-effects
            if (! $invoice->wasRecentlyCreated) {
                return $invoice;
            }

            // Load subscription lines with their tax and product relations
            $lines = $subscription->lines()->with(['tax', 'product'])->get();

            $subtotal      = 0.0;
            $taxTotal      = 0.0;
            $discountTotal = 0.0;

            // Determine applicable discounts for this billing cycle
            $applicableDiscounts = $this->discountEngine->applicable($subscription, $billingPeriodStart);

            // Per-line discount pool: product-level discounts
            $productDiscounts = $applicableDiscounts->filter(function ($d) {
                $appliesTo = $d->applies_to instanceof DiscountAppliesTo ? $d->applies_to->value : (string) $d->applies_to;
                return $appliesTo === 'product';
            });

            // Subscription-level discounts apply after line totals
            $subscriptionDiscounts = $applicableDiscounts->filter(function ($d) {
                $appliesTo = $d->applies_to instanceof DiscountAppliesTo ? $d->applies_to->value : (string) $d->applies_to;
                return $appliesTo === 'subscription';
            });

            $invoiceLines = [];

            foreach ($lines as $line) {
                $lineSubtotal = round((float) $line->quantity * (float) $line->unit_price, 2);
                $subtotal += $lineSubtotal;

                // Calculate per-line product discount
                $lineDiscount = 0.0;
                foreach ($productDiscounts as $discount) {
                    if ((int) $discount->product_id === (int) $line->product_id) {
                        $lineDiscount += $this->discountEngine->amountFor($discount, $lineSubtotal, (int) $line->quantity);
                    }
                }
                $discountTotal += $lineDiscount;

                // Tax is calculated on the net (after product-level discount)
                $taxableAmount = max(0, $lineSubtotal - $lineDiscount);
                $lineTax = $this->taxCalculator->calculate($taxableAmount, $line->tax);
                $taxTotal += $lineTax;

                $invoiceLines[] = [
                    'invoice_id'      => $invoice->id,
                    'product_id'      => $line->product_id,
                    'description'     => $line->product?->name ?? 'Subscription line',
                    'quantity'        => $line->quantity,
                    'unit_price'      => $line->unit_price,
                    'discount_amount' => $lineDiscount,
                    'tax_amount'      => $lineTax,
                    'amount'          => round($taxableAmount + $lineTax, 2),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }

            // Apply subscription-level discounts to the remaining subtotal
            $netAfterProductDiscounts = max(0, $subtotal - $discountTotal);
            foreach ($subscriptionDiscounts as $discount) {
                $subDiscount    = $this->discountEngine->amountFor($discount, $netAfterProductDiscounts, 0);
                $discountTotal += $subDiscount;
            }

            // Insert invoice lines
            if (! empty($invoiceLines)) {
                InvoiceLine::insert($invoiceLines);
            }

            // Update invoice totals
            $total = round($subtotal - $discountTotal + $taxTotal, 2);
            $invoice->update([
                'subtotal'       => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'tax_total'      => round($taxTotal, 2),
                'total'          => max($total, 0),
            ]);

            // Record discount usages and increment times_used
            foreach ($applicableDiscounts as $discount) {
                DiscountUsage::create([
                    'discount_id' => $discount->id,
                    'invoice_id'  => $invoice->id,
                ]);

                $discount->increment('times_used');
            }

            // Advance next_billing_date
            $subscription->update([
                'next_billing_date' => $this->advanceDate($billingPeriodStart, $period)->toDateString(),
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Advance a date by the given billing period.
     */
    protected function advanceDate(Carbon $date, string $period): Carbon
    {
        return match ($period) {
            'daily'   => $date->copy()->addDay(),
            'weekly'  => $date->copy()->addWeek(),
            'monthly' => $date->copy()->addMonth(),
            'yearly'  => $date->copy()->addYear(),
            default   => $date->copy()->addMonth(),
        };
    }

    /**
     * Generate a unique invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $year  = Carbon::now()->format('Y');
        $count = Invoice::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%s-%05d', $year, $count);
    }
}
