<?php

namespace App\Livewire\Portal;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ActivateAccount extends Component
{
    public string $token = '';

    public ?Customer $customer = null;

    public bool $isInvalid = false;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->customer = Customer::where('invite_token', $token)->first();

        if (! $this->customer) {
            $this->isInvalid = true;
        }
    }

    public function activate(): void
    {
        if ($this->isInvalid || ! $this->customer) {
            $this->addError('token', 'This invitation token is invalid or has already been used.');

            return;
        }

        $this->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(9)->mixedCase()->symbols(),
            ],
        ]);

        $user = $this->customer->user;

        if (! $user) {
            $user = User::where('email', $this->customer->email)->first();
        }

        if ($user) {
            $user->password = Hash::make($this->password);
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->save();
        } else {
            $user = User::create([
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'password' => Hash::make($this->password),
                'email_verified_at' => now(),
            ]);
        }

        if (! $user->hasRole('customer')) {
            $user->syncRoles(['customer']);
        }

        $this->customer->update([
            'user_id' => $user->id,
            'invite_token' => null,
        ]);

        Auth::login($user);

        session()->flash('status', 'Your customer portal account is now active!');

        $this->redirect(route('portal.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.portal.activate-account');
    }
}
