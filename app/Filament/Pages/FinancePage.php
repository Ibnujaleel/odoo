<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FinanceOverdueInvoicesTable;
use App\Filament\Widgets\FinanceRecentPaymentsTable;
use App\Filament\Widgets\FinanceStatsOverview;
use App\Filament\Widgets\FinanceUpcomingRenewalsTable;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;

class FinancePage extends Page
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Finance overview';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.finance-page';

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(3)
                ->compact()
                ->schema([
                    Forms\Components\Select::make('customerId')
                        ->label('Customer')
                        ->options(Customer::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('All Customers'),

                    Forms\Components\DatePicker::make('startDate')
                        ->label('Start Date')
                        ->default(now()->startOfMonth()),

                    Forms\Components\DatePicker::make('endDate')
                        ->label('End Date')
                        ->default(now()->endOfMonth()),
                ]),
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceStatsOverview::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            FinanceOverdueInvoicesTable::class,
            FinanceRecentPaymentsTable::class,
            FinanceUpcomingRenewalsTable::class,
        ];
    }
}
