<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Pages\Page;

class FinancePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Finance overview';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.finance-page';

    protected function getViewData(): array
    {
        return [
            'invoiced' => (float) Invoice::query()->whereNot('status', 'cancelled')->sum('total'),
            'collected' => (float) Payment::query()->sum('amount'),
            'outstanding' => (float) Invoice::query()->outstanding()->sum('total'),
            'overdueCount' => Invoice::query()->overdue()->count(),
        ];
    }
}
