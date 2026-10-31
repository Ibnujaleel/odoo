<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Brightloop · Subscription operations, simplified</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=outfit:500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-white antialiased landing-page">
        <div class="relative overflow-hidden landing-shell">
            <div class="pointer-events-none absolute -left-32 top-0 h-96 w-96 rounded-full bg-primary-600/30 blur-3xl landing-decoration"></div>
            <div class="pointer-events-none absolute -right-32 top-40 h-96 w-96 rounded-full bg-primary-400/20 blur-3xl landing-decoration"></div>

            <header class="relative mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8 landing-header">
                <a href="{{ route('home') }}" class="flex items-center gap-3 landing-brand">
                    <span class="flex h-10 w-10 items-center justify-center rounded-card bg-primary-500 font-bold landing-brand-mark">
                        <svg viewBox="0 0 48 48" fill="none" aria-hidden="true">
                            <path d="M17 13.5 12 10M31 13.5 36 10M14 31l-4 4M34 31l4 4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                            <circle cx="9" cy="8" r="3" fill="currentColor"/>
                            <circle cx="39" cy="8" r="3" fill="currentColor"/>
                            <circle cx="8" cy="38" r="3" fill="currentColor"/>
                            <circle cx="40" cy="38" r="3" fill="currentColor"/>
                            <circle cx="24" cy="17" r="5" fill="currentColor"/>
                            <path d="M14.5 34c.8-6 4.1-9 9.5-9s8.7 3 9.5 9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="landing-brand-copy">
                        <span class="text-xl font-semibold tracking-tight landing-wordmark"><span>bright</span><span>loop</span></span>
                    </span>
                </a>

                <nav class="flex items-center gap-2 landing-nav">
                    @auth
                        <a href="{{ auth()->user()->homeUrl() }}" class="btn-primary landing-nav-primary">Open workspace</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary border-white/20 bg-white/5 text-white hover:bg-white/10 landing-nav-secondary">Log in</a>
                        <a href="{{ route('register') }}" class="btn-primary landing-nav-primary">Get started</a>
                    @endauth
                </nav>
            </header>

            <main class="relative mx-auto max-w-7xl px-4 pb-20 pt-12 sm:px-6 lg:px-8 lg:pt-20 landing-main">
                <section class="landing-hero">
                    <div class="max-w-3xl landing-hero-copy">
                        <p class="landing-tagline">The CRM that keeps you in the loop</p>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary-300 landing-eyebrow">Subscription management</p>
                        <h1 class="mt-5 text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl landing-title">Quote, bill and collect without the busywork.</h1>
                        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-300 landing-description">Brightloop brings quotations, recurring subscriptions, invoices and payments into one clear system — for sales, billing and finance teams.</p>

                        <div class="mt-8 flex flex-wrap gap-3 landing-actions">
                            <a href="{{ route('register') }}" class="btn-primary min-h-[48px] px-6 text-base landing-action-primary">Create your account</a>
                            <a href="{{ route('login') }}" class="btn-secondary min-h-[48px] border-white/20 bg-white/5 px-6 text-base text-white hover:bg-white/10 landing-action-secondary">Sign in to the demo</a>
                        </div>

                        <p class="mt-4 text-sm text-slate-400 landing-demo">Demo logins: admin@brightloop.test · staff@brightloop.test · customer@brightloop.test · Brightloop@123</p>
                    </div>

                    <div class="landing-visual" aria-hidden="true">
                        <div class="landing-visual-card">
                            <div class="landing-visual-topbar">
                                <span class="landing-window-dots"><i></i><i></i><i></i></span>
                                <span class="landing-visual-caption">Customer relationships</span>
                                <span class="landing-visual-menu">•••</span>
                            </div>
                            <div class="landing-visual-content">
                                <div class="landing-loop-art">
                                    <svg viewBox="0 0 520 300" fill="none">
                                        <path class="landing-loop-line" d="M133 146 253 75l128 70-125 91-123-90Z"/>
                                        <path class="landing-loop-line landing-loop-line-secondary" d="m133 146 123 90 125-91"/>
                                        <circle class="landing-loop-node" cx="133" cy="146" r="34"/>
                                        <circle class="landing-loop-node" cx="253" cy="75" r="34"/>
                                        <circle class="landing-loop-node" cx="381" cy="145" r="34"/>
                                        <circle class="landing-loop-node landing-loop-node-center" cx="256" cy="236" r="38"/>
                                        <circle class="landing-loop-person" cx="133" cy="138" r="7"/>
                                        <path class="landing-loop-person" d="M119 160c1.5-8 6.3-12 14-12s12.5 4 14 12"/>
                                        <circle class="landing-loop-person" cx="253" cy="67" r="7"/>
                                        <path class="landing-loop-person" d="M239 89c1.5-8 6.3-12 14-12s12.5 4 14 12"/>
                                        <circle class="landing-loop-person" cx="381" cy="137" r="7"/>
                                        <path class="landing-loop-person" d="M367 159c1.5-8 6.3-12 14-12s12.5 4 14 12"/>
                                        <circle class="landing-loop-center-person" cx="256" cy="226" r="8"/>
                                        <path class="landing-loop-center-person" d="M240 251c1.7-9 7.1-14 16-14s14.3 5 16 14"/>
                                    </svg>
                                </div>
                                <div class="landing-visual-label">
                                    <span>Every conversation, connected</span>
                                    <span>Quotes <b>·</b> Subscriptions <b>·</b> Invoices</span>
                                </div>
                            </div>
                            <div class="landing-visual-bottom">
                                <span><i></i> Sales</span>
                                <span><i></i> Billing</span>
                                <span><i></i> Finance</span>
                            </div>
                        </div>
                        <span class="landing-visual-note">Keep every team in the loop</span>
                    </div>
                </section>

                <div class="mt-16 grid gap-4 md:grid-cols-3 landing-features">
                    <div class="rounded-card border border-white/10 bg-white/5 p-6 backdrop-blur landing-feature-card">
                        <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary-500/20 text-primary-200 landing-feature-icon">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M9 13h8M9 17h8"/></svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold landing-feature-title">Quotations that close</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300 landing-feature-description">Build subscription quotes with plans, products, taxes and discounts, then move them through a clear lifecycle.</p>
                    </div>

                    <div class="rounded-card border border-white/10 bg-white/5 p-6 backdrop-blur landing-feature-card">
                        <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary-500/20 text-primary-200 landing-feature-icon">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4v6h6M20 20v-6h-6"/><path d="M5.5 15a7 7 0 0011.9 2.4L20 14M4 10l2.6-3.4A7 7 0 0118.5 9"/></svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold landing-feature-title">Recurring billing on rails</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300 landing-feature-description">Daily, weekly, monthly and yearly cycles generate invoices automatically with the right period and due dates.</p>
                    </div>

                    <div class="rounded-card border border-white/10 bg-white/5 p-6 backdrop-blur landing-feature-card">
                        <span class="flex h-11 w-11 items-center justify-center rounded-card bg-primary-500/20 text-primary-200 landing-feature-icon">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 7H9.5a3.5 3.5 0 000 7H14a3.5 3.5 0 010 7H6"/></svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold landing-feature-title">Finance without surprises</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300 landing-feature-description">Track outstanding balances, overdue invoices and payments in real time, with a portal customers actually enjoy.</p>
                    </div>
                </div>
            </main>

            <footer class="relative border-t border-white/10 landing-footer">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 landing-footer-inner">
                    <p class="landing-footer-copy">© {{ date('Y') }} Brightloop. Subscription operations, simplified.</p>
                    <p class="landing-footer-note">Built with Laravel, Filament and Livewire.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
