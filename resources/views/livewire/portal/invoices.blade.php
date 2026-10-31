<div class="space-y-6">
    <x-ui.page-header title="My Invoices" subtitle="Billing history, amounts due and payment status." />

    <x-ui.data-table title="Invoices" :paginator="$invoices">
        <x-slot name="filters">
            <label class="sr-only" for="invoice-search">Search invoices</label>
            <input id="invoice-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search invoice number..." class="field-input sm:max-w-xs" />
        </x-slot>

        @if ($invoices->isEmpty())
            <div class="p-5">
                <x-ui.empty-state title="No invoices yet" description="Your invoices will appear here after the first billing run." />
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('number')" class="font-semibold">Invoice</button></th>
                        <th class="px-5 py-3">Period</th>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('due_date')" class="font-semibold">Due date</button></th>
                        <th class="px-5 py-3 text-right"><button type="button" wire:click="sortBy('total')" class="font-semibold">Total</button></th>
                        <th class="px-5 py-3 text-right">Paid</th>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('status')" class="font-semibold">Status</button></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($invoices as $invoice)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ $invoice->number }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $invoice->billing_period_start?->format('M j') }} – {{ $invoice->billing_period_end?->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $invoice->due_date?->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-slate-900">${{ number_format((float) $invoice->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-slate-600">${{ number_format((float) $invoice->amount_paid, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3"><x-ui.status-badge :status="$invoice->displayStatus()" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.data-table>
</div>
