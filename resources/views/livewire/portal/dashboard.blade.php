<div class="space-y-6">
    <x-ui.page-header
        title="Welcome back, {{ auth()->user()->name }}"
        subtitle="A snapshot of your subscriptions, billing and payments."
    />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Active subscriptions" :value="$activeSubscriptions" hint="Currently billing" tone="success" />
        <x-ui.stat-card label="Outstanding" value="${{ number_format($outstanding, 2) }}" hint="Unpaid balance" tone="warning" />
        <x-ui.stat-card label="Paid to date" value="${{ number_format($paidTotal, 2) }}" hint="Recorded payments" tone="primary" />
        <x-ui.stat-card label="Next billing" :value="$nextBilling ? \Illuminate\Support\Carbon::parse($nextBilling)->format('M j, Y') : '—'" hint="Upcoming invoice" tone="slate" />
    </div>

    <x-ui.data-table title="Recent invoices" subtitle="Your latest billing activity" :paginator="$invoices">
        @if ($invoices->isEmpty())
            <div class="p-5">
                <x-ui.empty-state title="No invoices yet" description="Invoices will appear here once your subscription starts billing." />
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Invoice</th>
                        <th class="px-5 py-3">Period</th>
                        <th class="px-5 py-3">Due</th>
                        <th class="px-5 py-3">Total</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($invoices as $invoice)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ $invoice->number }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $invoice->billing_period_start?->format('M j') }} – {{ $invoice->billing_period_end?->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $invoice->due_date?->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">${{ number_format((float) $invoice->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3"><x-ui.status-badge :status="$invoice->displayStatus()" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.data-table>
</div>
