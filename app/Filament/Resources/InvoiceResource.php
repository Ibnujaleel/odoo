<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('number')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->placeholder('Generated automatically'),
            Forms\Components\Select::make('subscription_id')
                ->relationship('subscription', 'number')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\DatePicker::make('billing_period_start')->required(),
                Forms\Components\DatePicker::make('billing_period_end')->afterOrEqual('billing_period_start')->required(),
                Forms\Components\DatePicker::make('due_date')->required(),
                Forms\Components\Select::make('status')
                    ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn (InvoiceStatus $status) => [$status->value => $status->label()]))
                    ->required()
                    ->default(InvoiceStatus::Draft->value),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('subtotal')->numeric()->minValue(0)->required()->prefix('$'),
                Forms\Components\TextInput::make('discount_total')->label('Discount total')->numeric()->minValue(0)->default(0)->prefix('$'),
                Forms\Components\TextInput::make('tax_total')->label('Tax total')->numeric()->minValue(0)->default(0)->prefix('$'),
                Forms\Components\TextInput::make('total')->numeric()->minValue(0)->required()->prefix('$'),
                Forms\Components\TextInput::make('amount_paid')->label('Amount paid')->numeric()->minValue(0)->default(0)->prefix('$'),
                Forms\Components\DateTimePicker::make('sent_at')->label('Sent at'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof InvoiceStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state) => match ($state instanceof InvoiceStatus ? $state->value : (string) $state) {
                        'paid' => 'success',
                        'confirmed' => 'primary',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('billing_period_start')->label('Period start')->date()->sortable(),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable()
                    ->color(fn (Invoice $record) => $record->isOverdue() ? 'danger' : null),
                Tables\Columns\TextColumn::make('total')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')->label('Paid')->money('USD')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
