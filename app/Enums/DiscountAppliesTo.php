<?php

namespace App\Enums;

enum DiscountAppliesTo: string
{
    case Product = 'product';
    case Subscription = 'subscription';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
