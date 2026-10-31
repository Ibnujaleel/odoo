<?php

namespace App\Livewire\Portal;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal')]
class Dashboard extends Component
{
    use WithPagination;

    public function render()
    {
        $customer = auth()->user()->customer;

        $invoices = $customer
            ? $customer->invoices()->with('subscription')->latest('id')->paginate(5)
            : Invoice::query()->whereRaw('1 = 0')->paginate(5);

        return view('livewire.portal.dashboard', [
            'customer' => $customer,
            'activeSubscriptions' => $customer?->subscriptions()->where('status', 'active')->count() ?? 0,
            'outstanding' => $customer ? (float) $customer->invoices()->outstanding()->sum(DB::raw('total - amount_paid')) : 0,
            'paidTotal' => $customer ? (float) $customer->payments()->sum('amount') : 0,
            'nextBilling' => $customer?->subscriptions()->where('status', 'active')->whereNotNull('next_billing_date')->min('next_billing_date'),
            'invoices' => $invoices,
        ]);
    }
}
