<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DiscountEngine
{
    /**
     * @return Collection<int, Discount>
     */
    public function applicable(Subscription $s, Carbon $on): Collection
    {
        return Discount::query()
            ->where('is_active', true)
            ->where(function ($query) use ($s) {
                $query->where('applies_to', 'subscription')
                    ->where(fn ($q) => $q->whereNull('subscription_id')->orWhere('subscription_id', $s->id));
            })
            ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $on))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $on))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit'))
            ->get();
    }

    public function amountFor(Discount $d, float $subtotal, int $qty): float
    {
        if ($subtotal < (float) $d->min_purchase || $qty < (int) $d->min_quantity) {
            return 0.0;
        }

        $amount = match ($d->type) {
            DiscountType::Fixed => (float) $d->value,
            DiscountType::Percentage => $subtotal * ((float) $d->value / 100),
        };

        return round(min($amount, $subtotal), 2);
    }
}
