<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'amount';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('method')
                ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->label()]))
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->prefix('$')
                ->required(),
            Forms\Components\DatePicker::make('paid_on')
                ->required()
                ->default(today()),
            Forms\Components\Textarea::make('note')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('paid_on')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->label() : ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('recorder.name')
                    ->label('Recorded by'),
                Tables\Columns\TextColumn::make('note')
                    ->limit(40),
            ])
            ->defaultSort('paid_on', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
