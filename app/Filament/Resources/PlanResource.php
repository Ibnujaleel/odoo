<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\BillingPeriod;
use App\Enums\PlanStatus;
use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 2;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')
                    ->description('Configure the subscription plan.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for this plan.'),

                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->helperText('The recurring price for this plan.'),

                        Forms\Components\Select::make('billing_period')
                            ->required()
                            ->options(collect(BillingPeriod::cases())->mapWithKeys(fn (BillingPeriod $p) => [$p->value => $p->label()]))
                            ->native(false)
                            ->helperText('How often subscribers are billed.'),

                        Forms\Components\TextInput::make('min_quantity')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->helperText('Minimum number of units required.'),

                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->helperText('When this plan becomes available.'),

                        Forms\Components\DatePicker::make('end_date')
                            ->after('start_date')
                            ->helperText('When this plan expires. Leave empty for no end date.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Plan Controls')
                    ->description('Configure lifecycle options for subscriptions on this plan.')
                    ->schema([
                        Forms\Components\Toggle::make('auto_close')
                            ->helperText('Automatically close subscriptions when plan end date passes.'),

                        Forms\Components\Toggle::make('closable')
                            ->default(true)
                            ->helperText('Allow subscriptions on this plan to be manually closed.'),

                        Forms\Components\Toggle::make('pausable')
                            ->helperText('Allow subscriptions on this plan to be paused.'),

                        Forms\Components\Toggle::make('renewable')
                            ->default(true)
                            ->helperText('Allow subscriptions on this plan to be renewed after expiry.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options(collect(PlanStatus::cases())->mapWithKeys(fn (PlanStatus $s) => [$s->value => $s->label()]))
                            ->default(PlanStatus::Active->value)
                            ->native(false)
                            ->helperText('The current status of this plan.'),
                    ]),
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
                    ->formatStateUsing(fn ($state) => $state instanceof BillingPeriod ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => match ($state instanceof BillingPeriod ? $state->value : (string) $state) {
                        'daily' => 'gray',
                        'weekly' => 'info',
                        'monthly' => 'primary',
                        'yearly' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_quantity')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('No end date'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PlanStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => match ($state instanceof PlanStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        'closed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('billing_period')
                    ->options(collect(BillingPeriod::cases())->mapWithKeys(fn (BillingPeriod $p) => [$p->value => $p->label()])),

                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(PlanStatus::cases())->mapWithKeys(fn (PlanStatus $s) => [$s->value => $s->label()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Close this plan?')
                    ->modalDescription('Closing a plan will prevent new subscriptions. Existing subscriptions remain active until they expire.')
                    ->visible(fn (Plan $record) => $record->closable && ($record->status instanceof PlanStatus ? $record->status === PlanStatus::Active : $record->status === 'active') && (auth()->user()?->isAdmin() ?? false))
                    ->action(function (Plan $record) {
                        $record->update(['status' => PlanStatus::Closed->value]);
                        Notification::make()
                            ->title('Plan closed')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Pause this plan?')
                    ->modalDescription('Pausing a plan will temporarily stop new subscriptions.')
                    ->visible(fn (Plan $record) => $record->pausable && ($record->status instanceof PlanStatus ? $record->status === PlanStatus::Active : $record->status === 'active') && (auth()->user()?->isAdmin() ?? false))
                    ->action(function (Plan $record) {
                        $record->update(['status' => PlanStatus::Paused->value]);
                        Notification::make()
                            ->title('Plan paused')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('renew')
                    ->label('Renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Renew this plan?')
                    ->modalDescription('This will reactivate the plan and allow new subscriptions.')
                    ->visible(fn (Plan $record) => $record->renewable && in_array($record->status instanceof PlanStatus ? $record->status->value : (string) $record->status, ['paused', 'closed'], true) && (auth()->user()?->isAdmin() ?? false))
                    ->action(function (Plan $record) {
                        $record->update(['status' => PlanStatus::Active->value]);
                        Notification::make()
                            ->title('Plan renewed')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
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
