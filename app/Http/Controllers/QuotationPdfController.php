<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class QuotationPdfController extends Controller
{
    /**
     * Render and stream the quotation PDF document.
     */
    public function show(Subscription $subscription): Response
    {
        Gate::authorize('view', $subscription);

        $subscription->loadMissing(['customer', 'plan', 'lines.product', 'lines.variant', 'lines.tax']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'subscription' => $subscription,
        ]);

        return $pdf->stream("quotation-{$subscription->number}.pdf");
    }
}
