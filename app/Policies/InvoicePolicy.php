<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->manage($user) || $this->owns($user, $invoice->customer_id);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'staff']);
    }

    private function owns(User $user, int $customerId): bool
    {
        return $user->hasRole('customer') && $user->customer?->id === $customerId;
    }
}
