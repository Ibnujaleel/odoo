<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Brightloop · Subscription operations, simplified</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-white antialiased">
        <div class="relative overflow-hidden">
            <div class="pointer-events-none absolute -left-32 top-0 h-96 w-96 rounded-full bg-primary-600/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-32 top-40 h-96 w-96 rounded-full bg-primary-400/20 blur-3xl"></div>

            <header class="relative mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-card bg-primary-500 font-bold">B</span>
                    <span class="text-xl font-semibold tracking-tight">Brightloop</span>
                </a>

                <nav class="flex items-center gap-2">
                    @auth
                        <a href="{{ auth()->user()->homeUrl() }}" class="btn-primary">Open workspace</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary border-white/20 bg-white/5 text-white hover:bg-white/10">Log in</a>
                        <a href="{{ route('register') }}" class="btn-primary">Get started</a>
                    @endauth
                </nav>
            </header>

            <main class="relative mx-auto max-w-7xl px-4 pb-20 pt-12 sm:px-6 lg:px-8 lg:pt-20">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary-300">Subscription management</p>
                    <h1 class="mt-5 text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl">Quote, bill and collect without the busywork.</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-300">Brightloop brings quotations, recurring subscriptions, invoices and payments into one clear system — for sales, billing and finance teams.</p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="btn-primary min-h-[48px] px-6 text-base">Create your account</a>
                        <a href="{{ route('login') }}" class="btn-secondary min-h-[48px] border-white/20 bg-white/5 px-6 text-base text-white hover:bg-white/10">Sign in to the demo</a>
                    </div>

                    <p class="mt-4 text-sm text-slate-400">Demo logins: admin@brightloop.test · staff@brightloop.test · customer@brightloop.test · Brightloop@123</p>
                </div>

                <div class="mt-16 grid gap-4 md:grid-cols-3">
                    <div class="rounded-card border border-white/10 bg-white/5 p-6 backdrop-blur">
                        <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary-500/20 text-primary-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M9 13h8M9 17h8"/></svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold">Quotations that close</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300">Build subscription quotes with plans, products, taxes and discounts, then move them through a clear lifecycle.</p>
                    </div>

                    <div class="rounded-card border border-white/10 bg-white/5 p-6 backdrop-blur">
                        <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary-500/20 text-primary-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4v6h6M20 20v-6h-6"/><path d="M5.5 15a7 7 0 0011.9 2.4L20 14M4 10l2.6-3.4A7 7 0 0118.5 9"/></svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold">Recurring billing on rails</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300">Daily, weekly, monthly and yearly cycles generate invoices automatically with the right period and due dates.</p>
                    </div>

                    <div class="rounded-card border border-white/10 bg-white/5 p-6 backdrop-blur">
                        <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary-500/20 text-primary-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 7H9.5a3.5 3.5 0 000 7H14a3.5 3.5 0 010 7H6"/></svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold">Finance without surprises</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300">Track outstanding balances, overdue invoices and payments in real time, with a portal customers actually enjoy.</p>
                    </div>
                </div>
            </main>

            <footer class="relative border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <p>© {{ date('Y') }} Brightloop. Subscription operations, simplified.</p>
                    <p>Built with Laravel, Filament and Livewire.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
