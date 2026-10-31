<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);

        $data['recorded_by'] = auth()->id();

        app(InvoiceService::class)->recordPayment($invoice, $data);

        return $invoice->payments()->latest('id')->first();
    }
}
