<div class="space-y-6">
    <x-ui.page-header title="My Quotations" subtitle="Review quotations prepared by the Brightloop team." />

    <x-ui.data-table title="Quotations" :paginator="$quotations">
        <x-slot name="filters">
            <label class="sr-only" for="quotation-search">Search quotations</label>
            <input id="quotation-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search number or plan..." class="field-input sm:max-w-xs" />
        </x-slot>

        @if ($quotations->isEmpty())
            <div class="p-5">
                <x-ui.empty-state title="No quotations yet" description="When our team prepares a quotation it will appear here." />
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('number')" class="font-semibold">Quote</button></th>
                        <th class="px-5 py-3">Plan</th>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('quotation_valid_until')" class="font-semibold">Valid until</button></th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($quotations as $quotation)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ $quotation->number }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $quotation->plan?->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $quotation->quotation_valid_until?->format('M j, Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3"><x-ui.status-badge :status="$quotation->status" /></td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <a href="{{ route('portal.quotations.show', $quotation) }}" class="font-semibold text-primary-600 hover:text-primary-500">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.data-table>
</div>
