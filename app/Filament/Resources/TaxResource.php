<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 3;

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
                Forms\Components\Section::make('Tax Details')
                    ->description('Configure a tax rule applied to invoice lines.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name, e.g. "GST 18%" or "Platform Fee".'),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed'      => 'Fixed Amount',
                            ])
                            ->native(false)
                            ->live()
                            ->helperText('Percentage is calculated on the net amount. Fixed is a flat fee.'),

                        Forms\Components\TextInput::make('rate')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn (Forms\Get $get) => $get('type') === 'percentage' ? 100 : null)
                            ->suffix(fn (Forms\Get $get) => $get('type') === 'percentage' ? '%' : '₹')
                            ->helperText(fn (Forms\Get $get) => $get('type') === 'percentage'
                                ? 'Percentage rate (0–100). E.g. 18 for 18%.'
                                : 'Fixed amount in currency. E.g. 49.99.'
                            ),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive taxes will not be applied to new invoices.'),
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
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'primary',
                        'fixed'      => 'info',
                        default      => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate')
                    ->formatStateUsing(fn (Tax $record) => $record->type === 'percentage'
                        ? number_format((float) $record->rate, 2) . '%'
                        : '₹' . number_format((float) $record->rate, 2)
                    )
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
                        'percentage' => 'Percentage',
                        'fixed'      => 'Fixed',
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
            'index'  => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit'   => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}

