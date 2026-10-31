@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'trend' => null,
    'tone' => 'primary',
])

@php
    $tones = [
        'primary' => 'bg-primary-50 text-primary-600',
        'success' => 'bg-success-50 text-success-600',
        'warning' => 'bg-warning-50 text-warning-600',
        'danger' => 'bg-danger-50 text-danger-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-card border border-slate-200 bg-white p-5 shadow-soft']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
        </div>
        @if ($icon)
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-card {{ $tones[$tone] ?? $tones['primary'] }}">
                {{ $icon }}
            </span>
        @endif
    </div>

    @if ($hint || $trend)
        <div class="mt-3 flex items-center gap-2 text-xs">
            @if ($trend)
                <span class="font-semibold text-success-600">{{ $trend }}</span>
            @endif
            @if ($hint)
                <span class="text-slate-500">{{ $hint }}</span>
            @endif
        </div>
    @endif
</div>
