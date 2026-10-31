<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class AdminRecentSubscriptions extends TableWidget
{
    protected static ?string $heading = 'Recent subscriptions';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Subscription::query()->with(['customer', 'plan'])->latest('id'))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof SubscriptionStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state) => match ($state instanceof SubscriptionStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'quotation' => 'info',
                        'confirmed' => 'primary',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('next_billing_date')
                    ->label('Next bill')
                    ->date(),
            ]);
    }
}
