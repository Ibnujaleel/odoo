@props(['name'])

@php
    $paths = [
        'home' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10',
        'document' => 'M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1zm7 0v5h5M9 13h8M9 17h8',
        'refresh' => 'M4 4v6h6M20 20v-6h-6M5.5 15a7 7 0 0011.9 2.4L20 14M4 10l2.6-3.4A7 7 0 0118.5 9',
        'receipt' => 'M6 3v18l3-2 3 2 3-2 3 2V3l-3 2-3-2-3 2-3-2zm3 6h6M9 13h6',
        'user' => 'M12 12a4 4 0 100-8 4 4 0 000 8zm-8 9a8 8 0 0116 0',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $paths[$name] ?? $paths['home'] }}" />
</svg>
