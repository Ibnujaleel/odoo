@props([
    'title' => null,
    'subtitle' => null,
    'paginator' => null,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-card border border-slate-200 bg-white shadow-soft']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                @if ($title)
                    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    @isset($filters)
        <div class="border-b border-slate-100 px-5 py-3">{{ $filters }}</div>
    @endisset

    <div class="w-full overflow-x-auto">
        {{ $slot }}
    </div>

    @if ($paginator)
        <footer class="border-t border-slate-100 bg-slate-50 px-5 py-3">
            {{ $paginator }}
        </footer>
    @endif
</div>
