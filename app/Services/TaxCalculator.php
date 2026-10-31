<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaxType;
use App\Models\Tax;

/**
 * Calculates tax amount for a given net price and tax rule.
 *
 * Supports two tax types:
 *  - percentage: net × rate / 100
 *  - fixed: flat rate amount
 *
 * Returns 0.0 when no tax is provided or tax is inactive.
 * All results are rounded to 2 decimal places.
 */
class TaxCalculator
{
    /**
     * Calculate the tax amount for the given net price.
     *
     * @param  float     $net  The net (pre-tax) amount.
     * @param  Tax|null  $tax  The tax rule to apply, or null for no tax.
     * @return float           The calculated tax amount, rounded to 2 decimals.
     */
    public function calculate(float $net, ?Tax $tax): float
    {
        if ($tax === null || ! $tax->is_active) {
            return 0.0;
        }

        $type = $tax->type instanceof TaxType ? $tax->type->value : (string) $tax->type;

        $amount = match ($type) {
            'percentage' => $net * ((float) $tax->rate / 100),
            'fixed'      => (float) $tax->rate,
            default      => 0.0,
        };

        return round($amount, 2);
    }
}
