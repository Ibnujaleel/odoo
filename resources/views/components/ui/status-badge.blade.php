@props(['status'])

@php
    $key = is_object($status) && property_exists($status, 'value') ? $status->value : (string) $status;
    $key = strtolower($key);

    $styles = [
        'draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'quotation' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'confirmed' => 'bg-primary-50 text-primary-700 ring-primary-200',
        'active' => 'bg-success-50 text-success-700 ring-success-200',
        'closed' => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
        'paid' => 'bg-success-50 text-success-700 ring-success-200',
        'cancelled' => 'bg-danger-50 text-danger-700 ring-danger-200',
        'overdue' => 'bg-red-50 text-red-700 ring-red-200',
        'pending' => 'bg-warning-50 text-warning-700 ring-warning-200',
    ];

    $label = str_contains($key, '_') ? str_replace('_', ' ', $key) : $key;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1 ring-inset '.($styles[$key] ?? 'bg-slate-100 text-slate-600 ring-slate-200')]) }}>
    {{ $label }}
</span>
