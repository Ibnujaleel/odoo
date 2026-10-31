<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationTemplateResource\Pages;
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Select::make('plan_id')
                ->relationship('plan', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('validity_days')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(365)
                ->required()
                ->default(30),
            Forms\Components\Repeater::make('lines')
                ->relationship()
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('variant_id')
                        ->relationship('variant', 'value')
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->default(1),
                ])
                ->columns(3)
                ->defaultItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('plan.name')->label('Plan')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('validity_days')->label('Validity (days)')->sortable(),
                Tables\Columns\TextColumn::make('lines_count')->counts('lines')->label('Lines'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
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
