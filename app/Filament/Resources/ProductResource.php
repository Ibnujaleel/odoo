<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 1;

    /**
     * Only admins can create, edit or delete products. Staff may view.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Details')
                    ->description('Basic product information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('A unique name for this product.'),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'subscription' => 'Subscription',
                                'service'      => 'Service',
                                'addon'        => 'Add-on',
                            ])
                            ->native(false)
                            ->helperText('The category this product belongs to.'),

                        Forms\Components\TextInput::make('sales_price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₹')
                            ->helperText('The price charged to customers.'),

                        Forms\Components\TextInput::make('cost_price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₹')
                            ->helperText('Your internal cost for this product.'),

                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->helperText('Internal notes or description for this product.'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive products will not appear in new quotations.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Product Variants')
                    ->description('Add variants like brand, edition, region, etc.')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('attribute')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('e.g. brand, edition, region')
                                    ->helperText('The variant attribute name.'),

                                Forms\Components\TextInput::make('value')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Odoo, Enterprise, US-East')
                                    ->helperText('The value for this attribute.'),

                                Forms\Components\TextInput::make('extra_price')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('₹')
                                    ->helperText('Additional price on top of the base sales price.'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Variant')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                ($state['attribute'] ?? '') . ': ' . ($state['value'] ?? '') .
                                (isset($state['extra_price']) && $state['extra_price'] > 0
                                    ? ' (+₹' . number_format((float) $state['extra_price'], 2) . ')'
                                    : '')
                            ),
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

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'subscription' => 'primary',
                        'service'      => 'info',
                        'addon'        => 'warning',
                        default        => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('sales_price')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost_price')
                    ->money('INR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'subscription' => 'Subscription',
                        'service'      => 'Service',
                        'addon'        => 'Add-on',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('admin')),
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
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

