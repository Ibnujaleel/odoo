<?php

namespace App\Livewire\Portal;

use App\Models\Subscription;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class QuotationShow extends Component
{
    public Subscription $subscription;

    public function mount(Subscription $subscription): void
    {
        Gate::authorize('view', $subscription);
        $this->subscription = $subscription->load(['plan', 'customer', 'lines.product', 'lines.variant']);
    }

    public function render()
    {
        return view('livewire.portal.quotation-show');
    }
}
