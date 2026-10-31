<?php

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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Select::make('type')
                ->options(collect(DiscountType::cases())->mapWithKeys(fn (DiscountType $type) => [$type->value => $type->label()]))
                ->required(),
            Forms\Components\TextInput::make('value')->numeric()->minValue(0)->required(),
            Forms\Components\Select::make('applies_to')
                ->label('Applies to')
                ->options(collect(DiscountAppliesTo::cases())->mapWithKeys(fn (DiscountAppliesTo $type) => [$type->value => $type->label()]))
                ->required(),
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('subscription_id')
                ->relationship('subscription', 'number')
                ->searchable()
                ->preload(),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('min_purchase')->numeric()->minValue(0)->default(0),
                Forms\Components\TextInput::make('min_quantity')->numeric()->integer()->minValue(0)->default(0),
                Forms\Components\DatePicker::make('start_date'),
                Forms\Components\DatePicker::make('end_date')->afterOrEqual('start_date'),
                Forms\Components\TextInput::make('usage_limit')->numeric()->integer()->minValue(1),
                Forms\Components\TextInput::make('times_used')->numeric()->integer()->disabled()->dehydrated(false),
            ]),
            Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof DiscountType ? $state->label() : ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('value')->numeric(decimalPlaces: 2)->sortable(),
                Tables\Columns\TextColumn::make('applies_to')
                    ->label('Applies to')
                    ->formatStateUsing(fn ($state) => $state instanceof DiscountAppliesTo ? $state->label() : ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('times_used')->label('Used')->sortable(),
                Tables\Columns\TextColumn::make('usage_limit')->label('Limit')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
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
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'view' => Pages\ViewDiscount::route('/{record}'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
