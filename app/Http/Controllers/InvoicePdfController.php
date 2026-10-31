<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class InvoicePdfController extends Controller
{
    /**
     * Render and stream the invoice PDF document.
     */
    public function show(Invoice $invoice): Response
    {
        Gate::authorize('view', $invoice);

        $invoice->loadMissing([
            'customer',
            'subscription.plan',
            'lines.product',
            'payments',
            'discounts',
        ]);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        return $pdf->stream("invoice-{$invoice->number}.pdf");
    }
}
