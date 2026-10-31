<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Brightloop') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ auth()->user()->homeUrl() }}" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-card bg-primary-600 text-sm font-bold text-white">B</span>
                    <span class="text-lg font-semibold tracking-tight">Brightloop</span>
                </a>

                <nav class="flex items-center gap-2">
                    <a href="{{ auth()->user()->homeUrl() }}" class="btn-secondary">Home</a>
                    <a href="{{ route('profile') }}" class="btn-secondary">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-primary">Log out</button>
                    </form>
                </nav>
            </div>
        </header>

        @if (isset($header))
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </div>
        @endif

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
        @livewireScripts
    </body>
</html>
