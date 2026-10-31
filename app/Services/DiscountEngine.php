<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Discount;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Determines which discounts apply to a subscription on a given date
 * and calculates the monetary value of each discount.
 *
 * Qualification rules (ALL must pass):
 *  1. is_active = true
 *  2. start_date <= $on <= end_date
 *  3. times_used < usage_limit (or usage_limit is null → unlimited)
 *  4. Subscription lines meet min_quantity and min_purchase thresholds
 *  5. Scope match:
 *     - applies_to = 'subscription' → discount.subscription_id matches
 *     - applies_to = 'product'      → discount.product_id appears in subscription lines
 *
 * Amount rules:
 *  - fixed:      min(value, subtotal)  — never exceed the subtotal
 *  - percentage:  subtotal × value / 100
 */
class DiscountEngine
{
    /**
     * Return all discounts that qualify for the given subscription on a date.
     *
     * @param  Subscription  $subscription  The subscription (with lines eager-loaded).
     * @param  Carbon        $on            The reference date for eligibility.
     * @return Collection<int, Discount>    Qualifying discount models.
     */
    public function applicable(Subscription $subscription, Carbon $on): Collection
    {
        // Pre-compute subscription-level totals from lines
        $lines = $subscription->relationLoaded('lines')
            ? $subscription->lines
            : $subscription->lines()->get();

        $totalQuantity = $lines->sum('quantity');
        $totalAmount   = $lines->sum('amount');
        $productIds    = $lines->pluck('product_id')->unique()->filter()->values();

        return Discount::query()
            ->where('is_active', true)
            ->where('start_date', '<=', $on)
            ->where('end_date', '>=', $on)
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                      ->orWhereColumn('times_used', '<', 'usage_limit');
            })
            ->get()
            ->filter(function (Discount $discount) use ($subscription, $totalQuantity, $totalAmount, $productIds) {
                // Check min_quantity threshold
                if ($discount->min_quantity !== null && $totalQuantity < $discount->min_quantity) {
                    return false;
                }

                // Check min_purchase threshold
                if ($discount->min_purchase !== null && $totalAmount < $discount->min_purchase) {
                    return false;
                }

                // Check scope match
                return match ($discount->applies_to) {
                    'subscription' => $discount->subscription_id === $subscription->id,
                    'product'      => $productIds->contains($discount->product_id),
                    default        => false,
                };
            })
            ->values();
    }

    /**
     * Calculate the discount amount for a single discount.
     *
     * @param  Discount  $discount  The discount to evaluate.
     * @param  float     $subtotal  The line or invoice subtotal before discount.
     * @param  int       $qty       The quantity (used for context; not in the formula per spec).
     * @return float                The discount amount, rounded to 2 decimals.
     */
    public function amountFor(Discount $discount, float $subtotal, int $qty): float
    {
        return match ($discount->type) {
            'fixed'      => round(min((float) $discount->value, $subtotal), 2),
            'percentage' => round($subtotal * (float) $discount->value / 100, 2),
            default      => 0.0,
        };
    }
}
