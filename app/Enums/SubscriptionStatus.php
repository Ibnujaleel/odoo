<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Draft = 'draft';
    case Quotation = 'quotation';
    case Confirmed = 'confirmed';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** @return array<int, self> */
    public static function ordered(): array
    {
        return [self::Draft, self::Quotation, self::Confirmed, self::Active, self::Closed];
    }

    public function position(): int
    {
        return array_search($this, self::ordered(), true);
    }
}
