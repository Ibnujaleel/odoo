<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $outstanding = Invoice::query()
            ->outstanding()
            ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')
            ->value('balance');

        return [
            Stat::make('Active subscriptions', Subscription::query()->active()->count())
                ->description('Subscriptions currently billing')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Customers', Customer::query()->count())
                ->description('Total customer records')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Revenue collected', '$'.number_format((float) Payment::query()->sum('amount'), 2))
                ->description('All recorded payments')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Outstanding', '$'.number_format((float) $outstanding, 2))
                ->description('Confirmed and draft balances')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
        ];
    }
}
