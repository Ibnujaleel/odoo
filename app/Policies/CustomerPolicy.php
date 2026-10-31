<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->manage($user) || $customer->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'staff']);
    }
}
