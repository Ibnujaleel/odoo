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
        <div class="grid min-h-screen lg:grid-cols-2">
            <aside class="relative hidden overflow-hidden bg-slate-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-primary-500/30 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-primary-700/40 blur-3xl"></div>

                <a href="{{ url('/') }}" class="relative flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary-500 text-lg font-bold">B</span>
                    <span class="text-xl font-semibold tracking-tight">Brightloop</span>
                </a>

                <div class="relative max-w-md">
                    <p class="text-sm font-semibold uppercase tracking-widest text-primary-300">Subscription operations</p>
                    <h1 class="mt-4 text-4xl font-semibold leading-tight">Every quote, subscription and invoice in one calm workspace.</h1>
                    <p class="mt-4 text-base leading-relaxed text-slate-300">Brightloop keeps sales, billing and finance aligned so recurring revenue runs itself.</p>
                </div>

                <p class="relative text-sm text-slate-400">Built for subscription teams that value clarity.</p>
            </aside>

            <main class="flex items-center justify-center px-4 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <span class="flex h-10 w-10 items-center justify-center rounded-card bg-primary-600 font-bold text-white">B</span>
                        <span class="text-lg font-semibold">Brightloop</span>
                    </div>

                    <div class="rounded-card border border-slate-200 bg-white p-6 shadow-soft sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-xs text-slate-500">
                        Demo: admin@brightloop.test · staff@brightloop.test · customer@brightloop.test · Brightloop@123
                    </p>
                </div>
            </main>
        </div>
        @livewireScripts
    </body>
</html>
