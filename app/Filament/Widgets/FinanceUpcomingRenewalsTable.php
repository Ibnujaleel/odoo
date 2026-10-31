<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SubscriptionResource;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FinanceUpcomingRenewalsTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Upcoming Renewals (Next 7 Days)';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['lg' => 1];

    public function table(Table $table): Table
    {
        $customerId = $this->filters['customerId'] ?? null;

        $today = Carbon::today();
        $next7Days = Carbon::today()->addDays(7);

        $query = Subscription::query()
            ->active()
            ->with(['customer', 'plan'])
            ->whereNotNull('next_billing_date')
            ->whereBetween('next_billing_date', [$today->toDateString(), $next7Days->toDateString()])
            ->when($customerId, fn (Builder $q) => $q->where('customer_id', $customerId))
            ->orderBy('next_billing_date', 'asc');

        return $table
            ->query($query)
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Subscription')
                    ->url(fn (Subscription $record) => SubscriptionResource::getUrl('view', ['record' => $record])),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->limit(15),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan'),

                Tables\Columns\TextColumn::make('next_billing_date')
                    ->label('Renewal Date')
                    ->date()
                    ->badge()
                    ->color('primary'),
            ]);
    }
}
