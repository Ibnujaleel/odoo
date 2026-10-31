<div class="space-y-6">
    <x-ui.page-header
        :title="'Quotation '.$subscription->number"
        :subtitle="$subscription->plan?->name"
    >
        <x-slot name="actions">
            <a href="{{ route('portal.quotations') }}" class="btn-secondary">Back to quotations</a>
        </x-slot>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Status" :value="$subscription->status->label()" hint="Current quotation status" tone="primary" />
        <x-ui.stat-card label="Valid until" :value="$subscription->quotation_valid_until?->format('M j, Y') ?? '—'" hint="Acceptance window" tone="warning" />
        <x-ui.stat-card label="Plan price" value="${{ number_format((float) $subscription->plan?->price, 2) }}" hint="Per {{ $subscription->plan?->billing_period?->label() }}" tone="success" />
    </div>

    <x-ui.card title="Quotation lines">
        @if ($subscription->lines->isEmpty())
            <x-ui.empty-state title="No lines on this quotation" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Variant</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3 text-right">Unit price</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($subscription->lines as $line)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $line->product?->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $line->variant?->displayName() ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $line->quantity }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">${{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900">${{ number_format((float) $line->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
