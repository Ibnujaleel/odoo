<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('invoice_id')
                ->relationship('invoice', 'number')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('method')
                ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->label()]))
                ->required(),
            Forms\Components\TextInput::make('amount')->numeric()->minValue(0.01)->required()->prefix('$'),
            Forms\Components\DatePicker::make('paid_on')->required()->default(today()),
            Forms\Components\Textarea::make('note')->rows(2)->columnSpanFull(),
            Forms\Components\Select::make('recorded_by')
                ->relationship('recorder', 'name')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.number')->label('Invoice')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('invoice.customer.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->label() : ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('paid_on')->date()->sortable(),
                Tables\Columns\TextColumn::make('recorder.name')->label('Recorded by')->toggleable(),
            ])
            ->defaultSort('paid_on', 'desc')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
