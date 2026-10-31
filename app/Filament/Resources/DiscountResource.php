<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 4;

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
                Forms\Components\Section::make('Discount Details')
                    ->description('Configure the discount rule.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for this discount.'),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options(collect(DiscountType::cases())->mapWithKeys(fn (DiscountType $t) => [$t->value => $t->label()]))
                            ->native(false)
                            ->live()
                            ->helperText('Fixed deducts flat amount; Percentage deducts percentage of subtotal.'),

                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn (Forms\Get $get) => $get('type') === DiscountType::Percentage->value || $get('type') === 'percentage' ? '%' : '$')
                            ->helperText(fn (Forms\Get $get) => $get('type') === DiscountType::Percentage->value || $get('type') === 'percentage'
                                ? 'Percentage value (e.g. 15 for 15% off).'
                                : 'Fixed amount to deduct (e.g. 200 for $200 off).'
                            ),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive discounts will not be applied.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Eligibility Thresholds')
                    ->description('Optional minimum requirements for this discount to apply.')
                    ->schema([
                        Forms\Components\TextInput::make('min_purchase')
                            ->label('Min purchase')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->helperText('Minimum order/subscription value required. Leave empty for no minimum.'),

                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Min quantity')
                            ->numeric()
                            ->minValue(0)
                            ->integer()
                            ->helperText('Minimum total quantity across all lines. Leave empty for no minimum.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validity Period')
                    ->description('The date range during which this discount is available.')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->helperText('First day the discount is valid.'),

                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->after('start_date')
                            ->helperText('Last day the discount is valid. Must be after start date.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Usage & Scope')
                    ->description('Control how many times and where this discount applies.')
                    ->schema([
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Usage limit')
                            ->numeric()
                            ->minValue(1)
                            ->integer()
                            ->helperText('Max number of times this discount can be used. Leave empty for unlimited.'),

                        Forms\Components\TextInput::make('times_used')
                            ->label('Times used')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Number of times this discount has been applied (read-only).'),

                        Forms\Components\Select::make('applies_to')
                            ->label('Applies to')
                            ->required()
                            ->options(collect(DiscountAppliesTo::cases())->mapWithKeys(fn (DiscountAppliesTo $a) => [$a->value => $a->label()]))
                            ->native(false)
                            ->live()
                            ->helperText('Whether this discount targets a specific product or a specific subscription.'),

                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('applies_to') === DiscountAppliesTo::Product->value || $get('applies_to') === 'product')
                            ->required(fn (Forms\Get $get) => $get('applies_to') === DiscountAppliesTo::Product->value || $get('applies_to') === 'product')
                            ->helperText('The product this discount applies to.'),

                        Forms\Components\Select::make('subscription_id')
                            ->label('Subscription')
                            ->relationship('subscription', 'number')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('applies_to') === DiscountAppliesTo::Subscription->value || $get('applies_to') === 'subscription')
                            ->required(fn (Forms\Get $get) => $get('applies_to') === DiscountAppliesTo::Subscription->value || $get('applies_to') === 'subscription')
                            ->helperText('The subscription this discount applies to.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof DiscountType ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => match ($state instanceof DiscountType ? $state->value : (string) $state) {
                        'fixed'      => 'info',
                        'percentage' => 'primary',
                        default      => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn (Discount $record) => ($record->type instanceof DiscountType ? $record->type === DiscountType::Percentage : $record->type === 'percentage')
                        ? number_format((float) $record->value, 2) . '%'
                        : '$' . number_format((float) $record->value, 2)
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('applies_to')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof DiscountAppliesTo ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => match ($state instanceof DiscountAppliesTo ? $state->value : (string) $state) {
                        'product'      => 'warning',
                        'subscription' => 'success',
                        default        => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('times_used')
                    ->label('Used')
                    ->formatStateUsing(fn (Discount $record) => $record->usage_limit
                        ? "{$record->times_used} / {$record->usage_limit}"
                        : "{$record->times_used} / ∞"
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(DiscountType::cases())->mapWithKeys(fn (DiscountType $t) => [$t->value => $t->label()])),

                Tables\Filters\SelectFilter::make('applies_to')
                    ->options(collect(DiscountAppliesTo::cases())->mapWithKeys(fn (DiscountAppliesTo $a) => [$a->value => $a->label()])),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index'  => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'view'   => Pages\ViewDiscount::route('/{record}'),
            'edit'   => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
