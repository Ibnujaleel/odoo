@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-card bg-success-50 px-4 py-3 text-sm font-medium text-success-700']) }}>
        {{ $status }}
    </div>
@endif
