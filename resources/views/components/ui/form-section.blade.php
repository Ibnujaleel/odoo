@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'grid gap-6 py-6 first:pt-0 last:pb-0 md:grid-cols-3']) }}>
    <div class="md:col-span-1">
        @if ($title)
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
        @endif
        @if ($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>

    <div class="space-y-4 md:col-span-2">
        {{ $slot }}
    </div>
</div>
