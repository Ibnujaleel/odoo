<div class="space-y-6">
    <x-ui.page-header title="Account" subtitle="Your Brightloop profile and billing details.">
        <x-slot name="actions">
            <a href="{{ route('profile') }}" class="btn-secondary">Edit login details</a>
        </x-slot>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card title="Profile">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Name</dt>
                    <dd class="font-medium text-slate-900">{{ auth()->user()->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Email</dt>
                    <dd class="font-medium text-slate-900">{{ auth()->user()->email }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Company</dt>
                    <dd class="font-medium text-slate-900">{{ $customer?->company ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Billing details">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Customer name</dt>
                    <dd class="font-medium text-slate-900">{{ $customer?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Phone</dt>
                    <dd class="font-medium text-slate-900">{{ $customer?->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Address</dt>
                    <dd class="font-medium text-slate-900">{{ $customer?->address ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>
    </div>
</div>
