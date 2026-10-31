<div class="space-y-6">
    <x-ui.page-header title="My Subscriptions" subtitle="Everything you have active or scheduled with Brightloop." />

    <x-ui.data-table title="Subscriptions" :paginator="$subscriptions">
        <x-slot name="filters">
            <label class="sr-only" for="subscription-search">Search subscriptions</label>
            <input id="subscription-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search number or plan..." class="field-input sm:max-w-xs" />
        </x-slot>

        @if ($subscriptions->isEmpty())
            <div class="p-5">
                <x-ui.empty-state title="No subscriptions yet" description="Your subscriptions will appear here after a quotation is confirmed." />
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('number')" class="font-semibold">Number</button></th>
                        <th class="px-5 py-3">Plan</th>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('status')" class="font-semibold">Status</button></th>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('start_date')" class="font-semibold">Started</button></th>
                        <th class="px-5 py-3"><button type="button" wire:click="sortBy('next_billing_date')" class="font-semibold">Next billing</button></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($subscriptions as $subscription)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ $subscription->number }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $subscription->plan?->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3"><x-ui.status-badge :status="$subscription->status" /></td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $subscription->start_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $subscription->next_billing_date?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.data-table>
</div>
