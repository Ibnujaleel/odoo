<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $this->manage($user) || $this->owns($user, $subscription->customer_id);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Subscription $subscription): bool
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
