@props([
    'title' => 'Nothing here yet',
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-card border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center']) }}>
    @if ($icon)
        <span class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-white text-primary-600 shadow-sm">
            {{ $icon }}
        </span>
    @endif
    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
