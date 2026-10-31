<?php

namespace App\Services;

use App\Enums\TaxType;
use App\Models\Tax;

class TaxCalculator
{
    public function calculate(float $net, ?Tax $tax): float
    {
        if ($tax === null || ! $tax->is_active) {
            return 0.0;
        }

        $amount = match ($tax->type) {
            TaxType::Percentage => $net * ((float) $tax->rate / 100),
            TaxType::Fixed => (float) $tax->rate,
        };

        return round($amount, 2);
    }
}
