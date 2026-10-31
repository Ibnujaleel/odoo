<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case Upi = 'upi';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer',
            self::Upi => 'UPI',
            default => ucfirst($this->value),
        };
    }
}
