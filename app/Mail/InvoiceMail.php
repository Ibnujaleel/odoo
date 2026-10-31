<?php

namespace App\Mail;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->number} from Brightloop",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice',
            with: [
                'invoice' => $this->invoice,
            ],
        );
    }

    public function attachments(): array
    {
        $this->invoice->loadMissing(['customer', 'subscription', 'lines.product', 'lines.variant', 'discounts']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $this->invoice,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "invoice-{$this->invoice->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
