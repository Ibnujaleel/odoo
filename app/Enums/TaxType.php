<?php

namespace App\Enums;

enum TaxType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
