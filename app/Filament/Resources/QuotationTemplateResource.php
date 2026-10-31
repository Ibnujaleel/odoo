<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationTemplateResource\Pages;
use App\Models\ProductVariant;
use App\Models\QuotationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuotationTemplateResource extends Resource
{
    protected static ?string $model = QuotationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 5;

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
                Forms\Components\Section::make('Template Details')
                    ->description('Configure the quotation template.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for this template (e.g. "Starter Bundle").'),

                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->relationship('plan', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The subscription plan linked to quotations from this template.'),

                        Forms\Components\TextInput::make('validity_days')
                            ->label('Validity (days)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->default(30)
                            ->suffix('days')
                            ->helperText('How many days a quotation from this template stays valid (1–365).'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Template Lines')
                    ->description('Add the products and quantities included in this template.')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->helperText('Select the product to include.'),

                                Forms\Components\Select::make('variant_id')
                                    ->label('Variant')
                                    ->options(fn (Forms\Get $get) => $get('product_id')
                                        ? ProductVariant::where('product_id', $get('product_id'))
                                            ->get()
                                            ->mapWithKeys(fn ($v) => [
                                                $v->id => "{$v->attribute}: {$v->value}".
                                                    ($v->extra_price > 0 ? ' (+$'.number_format((float) $v->extra_price, 2).')' : ''),
                                            ])
                                        : []
                                    )
                                    ->searchable()
                                    ->helperText('Optionally select a specific variant.'),

                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->helperText('How many units of this product.'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Line')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['product_id'] ?? null)
                                    ? 'Product #'.$state['product_id'].' × '.($state['quantity'] ?? 1)
                                    : 'New line'
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

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('validity_days')
                    ->label('Validity')
                    ->suffix(' days')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lines_count')
                    ->counts('lines')
                    ->label('Lines')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
            'index' => Pages\ListQuotationTemplates::route('/'),
            'create' => Pages\CreateQuotationTemplate::route('/create'),
            'view' => Pages\ViewQuotationTemplate::route('/{record}'),
            'edit' => Pages\EditQuotationTemplate::route('/{record}/edit'),
        ];
    }
}
