<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentMethod;
use App\Filament\Resources\InvoiceResource;
use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FinanceRecentPaymentsTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Recent Payments';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['lg' => 1];

    public function table(Table $table): Table
    {
        $customerId = $this->filters['customerId'] ?? null;
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $query = Payment::query()
            ->with(['invoice.customer', 'recorder'])
            ->when($customerId, fn (Builder $q) => $q->whereHas('invoice', fn ($iq) => $iq->where('customer_id', $customerId)))
            ->when($startDate, fn (Builder $q) => $q->whereDate('paid_on', '>=', $startDate))
            ->when($endDate, fn (Builder $q) => $q->whereDate('paid_on', '<=', $endDate))
            ->latest('paid_on')
            ->latest('id')
            ->limit(10);

        return $table
            ->query($query)
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('paid_on')
                    ->label('Date')
                    ->date(),

                Tables\Columns\TextColumn::make('invoice.number')
                    ->label('Invoice')
                    ->url(fn (Payment $record) => $record->invoice_id ? InvoiceResource::getUrl('view', ['record' => $record->invoice_id]) : null),

                Tables\Columns\TextColumn::make('invoice.customer.name')
                    ->label('Customer')
                    ->limit(15),

                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->label() : ucfirst(str_replace('_', ' ', (string) $state))),

                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->weight('bold'),
            ]);
    }
}
