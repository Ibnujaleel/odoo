<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminRecentSubscriptions;
use App\Filament\Widgets\AdminRevenueChart;
use App\Filament\Widgets\AdminStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationGroup = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            AdminStatsOverview::class,
            AdminRevenueChart::class,
            AdminRecentSubscriptions::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
