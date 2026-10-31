<?php

namespace App\Filament\Resources;

use App\Enums\BillingPeriod;
use App\Enums\PlanStatus;
use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->minValue(0)
                ->required()
                ->prefix('$'),
            Forms\Components\Select::make('billing_period')
                ->options(collect(BillingPeriod::cases())->mapWithKeys(fn (BillingPeriod $period) => [$period->value => $period->label()]))
                ->required(),
            Forms\Components\TextInput::make('min_quantity')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->required()
                ->default(1),
            Forms\Components\DatePicker::make('start_date'),
            Forms\Components\DatePicker::make('end_date')
                ->afterOrEqual('start_date'),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Toggle::make('auto_close'),
                Forms\Components\Toggle::make('closable')->default(true),
                Forms\Components\Toggle::make('pausable'),
                Forms\Components\Toggle::make('renewable')->default(true),
            ]),
            Forms\Components\Select::make('status')
                ->options(collect(PlanStatus::cases())->mapWithKeys(fn (PlanStatus $status) => [$status->value => $status->label()]))
                ->required()
                ->default(PlanStatus::Active->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_period')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof BillingPeriod ? $state->label() : ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('min_quantity')
                    ->label('Min qty')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => ($state instanceof PlanStatus ? $state->value : (string) $state) === 'active' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('Subscriptions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'view' => Pages\ViewPlan::route('/{record}'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
