<?php

namespace App\Livewire\Portal;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\QuotationCalculator;
use App\Services\SubscriptionStateMachine;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class QuotationShow extends Component
{
    public Subscription $subscription;

    public string $rejection_reason = '';

    public bool $showRejectModal = false;

    public bool $showPaymentStep = false;

    public string $paymentMethod = 'card';

    public string $paymentNote = '';

    public bool $paymentSuccess = false;

    public array $calc = [
        'subtotal' => 0.0,
        'tax' => 0.0,
        'discount' => 0.0,
        'grand_total' => 0.0,
    ];

    public function mount(Subscription $subscription): void
    {
        Gate::authorize('view', $subscription);

        $this->subscription = $subscription->loadMissing([
            'plan',
            'customer',
            'lines.product',
            'lines.variant',
            'lines.tax',
        ]);

        $this->calculateTotals();

        if ($this->subscription->status === SubscriptionStatus::Confirmed) {
            $this->showPaymentStep = true;
        } elseif ($this->subscription->status === SubscriptionStatus::Active) {
            $this->paymentSuccess = true;
        }
    }

    public function calculateTotals(): void
    {
        try {
            $this->calc = app(QuotationCalculator::class)->calculate(
                $this->subscription->lines->all()
            );
        } catch (\Throwable $e) {
            $subtotal = (float) $this->subscription->lines->sum('amount');
            $this->calc = [
                'subtotal' => $subtotal,
                'tax' => 0.0,
                'discount' => 0.0,
                'grand_total' => $subtotal,
            ];
        }
    }

    public function isExpired(): bool
    {
        return $this->subscription->quotation_valid_until
            && $this->subscription->quotation_valid_until->isPast()
            && $this->subscription->status === SubscriptionStatus::Quotation;
    }

    public function accept(): void
    {
        Gate::authorize('view', $this->subscription);

        if ($this->isExpired()) {
            $this->addError('status', 'This quotation has expired and can no longer be accepted. Please contact sales to request a renewal.');

            return;
        }

        if ($this->subscription->status !== SubscriptionStatus::Quotation) {
            $this->addError('status', 'This quotation is not in a pending state.');

            return;
        }

        app(SubscriptionStateMachine::class)->transition($this->subscription, SubscriptionStatus::Confirmed->value);
        $this->subscription->refresh();
        $this->showPaymentStep = true;

        session()->flash('status', 'Quotation accepted! Please review payment terms below to activate.');
    }

    public function openRejectModal(): void
    {
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->resetValidation('rejection_reason');
    }

    public function reject(): void
    {
        Gate::authorize('view', $this->subscription);

        $this->validate([
            'rejection_reason' => 'required|min:5|max:500',
        ], [
            'rejection_reason.required' => 'Please provide a reason for rejecting this quotation.',
            'rejection_reason.min' => 'The rejection reason must be at least 5 characters.',
        ]);

        // State machine allows Active -> Closed; for quotation we mark closed with recorded note
        $this->subscription->status = SubscriptionStatus::Closed;
        $this->subscription->payment_terms = 'Rejected: '.$this->rejection_reason;
        $this->subscription->save();
        $this->subscription->refresh();

        $this->showRejectModal = false;
        session()->flash('status', 'Quotation has been rejected and closed.');
    }

    public function proceedToPayment(): void
    {
        $this->showPaymentStep = true;
    }

    public function confirmPaymentAndActivate(): void
    {
        Gate::authorize('view', $this->subscription);

        if ($this->subscription->status !== SubscriptionStatus::Confirmed) {
            $this->addError('payment', 'Quotation must be confirmed before proceeding to activation.');

            return;
        }

        app(SubscriptionStateMachine::class)->transition($this->subscription, SubscriptionStatus::Active->value);
        $this->subscription->refresh();
        $this->paymentSuccess = true;

        session()->flash('status', 'Payment terms confirmed! Your subscription is now active.');
    }

    public function render()
    {
        return view('livewire.portal.quotation-show');
    }
}
