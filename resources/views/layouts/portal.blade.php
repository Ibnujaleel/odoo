@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'portal.dashboard', 'icon' => 'home'],
        ['label' => 'My Quotations', 'route' => 'portal.quotations', 'icon' => 'document'],
        ['label' => 'My Subscriptions', 'route' => 'portal.subscriptions', 'icon' => 'refresh'],
        ['label' => 'My Invoices', 'route' => 'portal.invoices', 'icon' => 'receipt'],
        ['label' => 'Account', 'route' => 'portal.account', 'icon' => 'user'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Brightloop Portal' }} · Brightloop</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-card bg-primary-600 text-sm font-bold text-white">B</span>
                    <span class="text-lg font-semibold tracking-tight text-slate-900">Brightloop</span>
                </a>

                <div class="flex items-center gap-3">
                    <a href="{{ route('profile') }}" class="hidden text-sm font-medium text-slate-600 hover:text-slate-900 sm:block">
                        {{ auth()->user()->name }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary min-h-[40px] px-3">Log out</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="mx-auto flex max-w-7xl gap-8 px-4 py-6 sm:px-6 lg:px-8">
            <aside class="hidden w-56 shrink-0 lg:block">
                <nav class="sticky top-24 space-y-1">
                    @foreach ($nav as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'flex min-h-[44px] items-center gap-3 rounded-card px-3 text-sm font-medium transition',
                                'bg-primary-50 text-primary-700' => request()->routeIs($item['route'].'*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($item['route'].'*'),
                            ])
                        >
                            <x-portal.icon :name="$item['icon']" class="h-5 w-5" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <main class="min-w-0 flex-1 pb-24 lg:pb-0">
                {{ $slot }}
            </main>
        </div>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white lg:hidden">
            <div class="grid grid-cols-5">
                @foreach ($nav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'flex min-h-[56px] flex-col items-center justify-center gap-1 px-1 text-[10px] font-medium',
                            'text-primary-600' => request()->routeIs($item['route'].'*'),
                            'text-slate-500' => ! request()->routeIs($item['route'].'*'),
                        ])
                    >
                        <x-portal.icon :name="$item['icon']" class="h-5 w-5" />
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
        @livewireScripts
    </body>
</html>
