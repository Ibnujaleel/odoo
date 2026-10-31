<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <p class="text-sm text-gray-500">Invoiced</p>
            <p class="mt-1 text-2xl font-semibold">${{ number_format($invoiced, 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Collected</p>
            <p class="mt-1 text-2xl font-semibold text-success-600">${{ number_format($collected, 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Outstanding</p>
            <p class="mt-1 text-2xl font-semibold text-warning-600">${{ number_format($outstanding, 2) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Overdue invoices</p>
            <p class="mt-1 text-2xl font-semibold text-danger-600">{{ $overdueCount }}</p>
        </x-filament::section>
    </div>

    <x-filament::section>
        <p class="text-sm text-gray-500">Finance reporting workspace is ready. Dev 4 can add ageing buckets, collection trends and export here.</p>
    </x-filament::section>
</x-filament-panels::page>
