<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        $customerId = $this->filters['customerId'] ?? null;

        // 1. Active subscriptions
        $activeSubsQuery = Subscription::query()->active();
        if ($customerId) {
            $activeSubsQuery->where('customer_id', $customerId);
        }
        $activeSubs = $activeSubsQuery->count();

        // 2. Revenue collected (with optional month/date filter and customer filter)
        $paymentQuery = Payment::query();
        if ($customerId) {
            $paymentQuery->whereHas('invoice', fn ($q) => $q->where('customer_id', $customerId));
        }
        if ($startDate) {
            $paymentQuery->whereDate('paid_on', '>=', $startDate);
        }
        if ($endDate) {
            $paymentQuery->whereDate('paid_on', '<=', $endDate);
        }
        $revenueCollected = (float) $paymentQuery->sum('amount');

        // 3. Outstanding balance (aggregate query: total - amount_paid on outstanding invoices)
        $outstandingQuery = Invoice::query()->outstanding();
        if ($customerId) {
            $outstandingQuery->where('customer_id', $customerId);
        }
        $outstandingBalance = (float) $outstandingQuery->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')->value('balance');

        // 4. Overdue invoice count
        $overdueQuery = Invoice::query()->overdue();
        if ($customerId) {
            $overdueQuery->where('customer_id', $customerId);
        }
        $overdueCount = $overdueQuery->count();

        return [
            Stat::make('Active subscriptions', $activeSubs)
                ->description('Currently active recurring subscriptions')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('success'),

            Stat::make('Revenue collected', '$'.number_format($revenueCollected, 2))
                ->description($startDate || $endDate ? 'Filtered date range' : 'Total payments collected')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Outstanding balance', '$'.number_format($outstandingBalance, 2))
                ->description('Confirmed and draft balances due')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Overdue invoices', $overdueCount)
                ->description('Past due date with pending balance')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),
        ];
    }
}
