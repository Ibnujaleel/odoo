<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->manage($user)
            || ($user->hasRole('customer') && $user->customer?->id === $payment->invoice?->customer_id);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'staff']);
    }
}
