<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentMethod;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FinanceOverdueInvoicesTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Overdue Invoices';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $customerId = $this->filters['customerId'] ?? null;

        $query = Invoice::query()
            ->overdue()
            ->with(['customer', 'subscription'])
            ->when($customerId, fn (Builder $q) => $q->where('customer_id', $customerId))
            ->orderBy('due_date', 'asc');

        return $table
            ->query($query)
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Invoice')
                    ->searchable()
                    ->url(fn (Invoice $record) => InvoiceResource::getUrl('view', ['record' => $record])),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->state(fn (Invoice $record): float => $record->balance())
                    ->money('USD')
                    ->weight('bold')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->state(fn (Invoice $record): string => $record->due_date ? $record->due_date->diffInDays(today()).' days' : '-')
                    ->badge()
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('recordPayment')
                    ->label('Record payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
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
            ]);
    }
}
