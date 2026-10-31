<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceService
{
    public function confirm(Invoice $i): Invoice
    {
        return $i;
    }

    public function cancel(Invoice $i): Invoice
    {
        return $i;
    }

    public function send(Invoice $i): Invoice
    {
        return $i;
    }

    public function recordPayment(Invoice $i, array $data): Invoice
    {
        return $i;
    }
}
