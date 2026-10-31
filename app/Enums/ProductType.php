<?php

namespace App\Enums;

enum ProductType: string
{
    case Product = 'product';
    case Service = 'service';
    case Addon = 'addon';
    case Subscription = 'subscription';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
