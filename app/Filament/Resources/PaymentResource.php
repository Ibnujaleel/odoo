<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Record Payment')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('invoice_id')
                        ->label('Invoice')
                        ->options(function () {
                            return Invoice::query()
                                ->whereIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Confirmed->value])
                                ->whereColumn('total', '>', 'amount_paid')
                                ->with('customer')
                                ->get()
                                ->mapWithKeys(function (Invoice $inv) {
                                    $balance = number_format($inv->balance(), 2);
                                    $cust = $inv->customer?->name ?? 'Unknown';

                                    return [$inv->id => "{$inv->number} - {$cust} (Balance: \${$balance})"];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) {
                                return;
                            }
                            $invoice = Invoice::find($state);
                            if ($invoice) {
                                $set('amount', $invoice->balance());
                            }
                        }),

                    Forms\Components\Select::make('method')
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->label()]))
                        ->default(PaymentMethod::Card->value)
                        ->required(),

                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0.01)
                        ->required()
                        ->helperText(function (Forms\Get $get) {
                            $invId = $get('invoice_id');
                            if (! $invId) {
                                return null;
                            }
                            $invoice = Invoice::find($invId);

                            return $invoice ? 'Max allowed: $'.number_format($invoice->balance(), 2) : null;
                        }),

                    Forms\Components\DatePicker::make('paid_on')
                        ->required()
                        ->maxDate(today())
                        ->default(today()),

                    Forms\Components\Textarea::make('note')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['invoice.customer', 'recorder']))
            ->columns([
                Tables\Columns\TextColumn::make('paid_on')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice.number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Payment $record) => $record->invoice_id ? InvoiceResource::getUrl('view', ['record' => $record->invoice_id]) : null),

                Tables\Columns\TextColumn::make('invoice.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->label() : ucfirst(str_replace('_', ' ', (string) $state))),

                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('note')
                    ->limit(35)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('recorder.name')
                    ->label('Recorded by')
                    ->toggleable(),
            ])
            ->defaultSort('paid_on', 'desc')
            ->paginated([10, 25, 50])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->label()])),

                Tables\Filters\Filter::make('paid_on_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Paid from'),
                        Forms\Components\DatePicker::make('until')->label('Paid until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('paid_on', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('paid_on', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
