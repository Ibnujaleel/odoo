<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;

class BillingService
{
    public function generateFor(Subscription $s): ?Invoice
    {
        return null;
    }
}
