<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Invoice Overview')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->placeholder('Auto-generated (e.g. INV-2026-00001)')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('subscription_id')
                        ->relationship('subscription', 'number')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn (InvoiceStatus $status) => [$status->value => $status->label()]))
                        ->required()
                        ->default(InvoiceStatus::Draft->value),

                    Forms\Components\DatePicker::make('billing_period_start')
                        ->label('Period Start')
                        ->required(),

                    Forms\Components\DatePicker::make('billing_period_end')
                        ->label('Period End')
                        ->afterOrEqual('billing_period_start')
                        ->required(),

                    Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date')
                        ->required(),

                    Forms\Components\DateTimePicker::make('sent_at')
                        ->label('Sent At')
                        ->disabled(),
                ]),

            Forms\Components\Section::make('Invoice Lines')
                ->schema([
                    Forms\Components\Repeater::make('lines')
                        ->relationship('lines')
                        ->columns(6)
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->relationship('product', 'name')
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('description')
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),

                            Forms\Components\TextInput::make('unit_price')
                                ->numeric()
                                ->prefix('$')
                                ->required(),

                            Forms\Components\TextInput::make('discount_amount')
                                ->label('Discount')
                                ->numeric()
                                ->prefix('$')
                                ->default(0),

                            Forms\Components\TextInput::make('tax_amount')
                                ->label('Tax')
                                ->numeric()
                                ->prefix('$')
                                ->default(0),

                            Forms\Components\TextInput::make('amount')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->columnSpan(2),
                        ]),
                ]),

            Forms\Components\Section::make('Totals')
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->numeric()
                        ->prefix('$')
                        ->required(),

                    Forms\Components\TextInput::make('discount_total')
                        ->numeric()
                        ->prefix('$')
                        ->default(0),

                    Forms\Components\TextInput::make('tax_total')
                        ->numeric()
                        ->prefix('$')
                        ->default(0),

                    Forms\Components\TextInput::make('total')
                        ->numeric()
                        ->prefix('$')
                        ->required(),

                    Forms\Components\TextInput::make('amount_paid')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'subscription.plan']))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription.number')
                    ->label('Subscription')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof InvoiceStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state) => match ($state instanceof InvoiceStatus ? $state->value : (string) $state) {
                        'paid' => 'success',
                        'confirmed' => 'primary',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('overdue_days')
                    ->label('Overdue')
                    ->state(function (Invoice $record): ?string {
                        if (! $record->isOverdue() || ! $record->due_date) {
                            return null;
                        }
                        $days = (int) $record->due_date->diffInDays(today());

                        return $days === 0 ? 'Today' : "{$days}d overdue";
                    })
                    ->badge()
                    ->color('danger')
                    ->visible(fn () => true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn (InvoiceStatus $status) => [$status->value => $status->label()])),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue invoices')
                    ->query(fn (Builder $query): Builder => $query->overdue()),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('due_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')->label('Due from'),
                        Forms\Components\DatePicker::make('due_until')->label('Due until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['due_from'], fn (Builder $q, $date) => $q->whereDate('due_date', '>=', $date))
                            ->when($data['due_until'], fn (Builder $q, $date) => $q->whereDate('due_date', '<=', $date));
                    }),
            ])
            ->actions([
                // Confirm action
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn (Invoice $record): bool => ($record->status instanceof InvoiceStatus ? $record->status->value : (string) $record->status) === InvoiceStatus::Draft->value)
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Invoice')
                    ->modalDescription(fn (Invoice $record) => "Are you sure you want to confirm invoice {$record->number}? Once confirmed, it can receive payments.")
                    ->action(function (Invoice $record) {
                        try {
                            app(InvoiceService::class)->confirm($record);
                            Notification::make()->title('Invoice Confirmed')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // Cancel action
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Invoice $record): bool => in_array($record->status instanceof InvoiceStatus ? $record->status->value : (string) $record->status, [InvoiceStatus::Draft->value, InvoiceStatus::Confirmed->value], true) && ! $record->payments()->exists() && (float) $record->amount_paid == 0)
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Invoice')
                    ->modalDescription(fn (Invoice $record) => "Cancel invoice {$record->number}? This action cannot be undone.")
                    ->action(function (Invoice $record) {
                        try {
                            app(InvoiceService::class)->cancel($record);
                            Notification::make()->title('Invoice Cancelled')->warning()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // Send action
                Tables\Actions\Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice Email')
                    ->modalDescription(fn (Invoice $record) => "Send invoice {$record->number} with attached PDF to {$record->customer?->email}?")
                    ->action(function (Invoice $record) {
                        try {
                            app(InvoiceService::class)->send($record);
                            Notification::make()->title('Invoice Sent')->body("Email sent to {$record->customer?->email}")->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // Print PDF action
                Tables\Actions\Action::make('printPdf')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Invoice $record) => route('portal.invoices.pdf', $record))
                    ->openUrlInNewTab(),

                // Record Payment modal action
                Tables\Actions\Action::make('recordPayment')
                    ->label('Record payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => in_array($record->status instanceof InvoiceStatus ? $record->status->value : (string) $record->status, [InvoiceStatus::Draft->value, InvoiceStatus::Confirmed->value], true) && $record->balance() > 0)
                    ->form(function (Invoice $record) {
                        return [
                            Forms\Components\TextInput::make('invoice_info')
                                ->label('Invoice')
                                ->default("{$record->number} (Balance: \$".number_format($record->balance(), 2).')')
                                ->disabled(),
                            Forms\Components\Select::make('method')
                                ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->label()]))
                                ->default(PaymentMethod::Card->value)
                                ->required(),
                            Forms\Components\TextInput::make('amount')
                                ->numeric()
                                ->minValue(0.01)
                                ->maxValue($record->balance())
                                ->default($record->balance())
                                ->prefix('$')
                                ->required(),
                            Forms\Components\DatePicker::make('paid_on')
                                ->required()
                                ->maxDate(today())
                                ->default(today()),
                            Forms\Components\Textarea::make('note')
                                ->maxLength(500)
                                ->rows(2),
                        ];
                    })
                    ->action(function (Invoice $record, array $data) {
                        try {
                            app(InvoiceService::class)->recordPayment($record, $data);
                            Notification::make()->title('Payment Recorded')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
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
