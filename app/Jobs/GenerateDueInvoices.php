<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generates invoices for all active subscriptions whose next_billing_date is due.
 *
 * Processes in chunks of 200. Each subscription is wrapped in its own
 * try/catch so that one failure does not prevent the rest from billing.
 */
class GenerateDueInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of invoices created during this run.
     */
    public int $invoicesCreated = 0;

    /**
     * The number of subscriptions that failed during this run.
     */
    public int $failures = 0;

    /**
     * Execute the job.
     */
    public function handle(BillingService $billingService): void
    {
        $today = Carbon::today();

        Log::info('[GenerateDueInvoices] Starting billing run', [
            'date' => $today->toDateString(),
        ]);

        Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', $today)
            ->with(['plan', 'lines.tax', 'lines.product', 'customer'])
            ->chunkById(200, function ($subscriptions) use ($billingService) {
                foreach ($subscriptions as $subscription) {
                    try {
                        $invoice = $billingService->generateFor($subscription);

                        if ($invoice && $invoice->wasRecentlyCreated) {
                            $this->invoicesCreated++;

                            Log::info('[GenerateDueInvoices] Invoice created', [
                                'invoice_id'      => $invoice->id,
                                'invoice_number'  => $invoice->number,
                                'subscription_id' => $subscription->id,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        $this->failures++;

                        Log::error('[GenerateDueInvoices] Failed to generate invoice', [
                            'subscription_id' => $subscription->id,
                            'error'           => $e->getMessage(),
                            'trace'           => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        Log::info('[GenerateDueInvoices] Billing run complete', [
            'invoices_created' => $this->invoicesCreated,
            'failures'         => $this->failures,
        ]);
    }
}
