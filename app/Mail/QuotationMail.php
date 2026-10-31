<?php

namespace App\Mail;

use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription)
    {
        $this->subscription->loadMissing(['customer', 'plan', 'lines.product', 'lines.variant', 'lines.tax']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Brightloop Quotation {$this->subscription->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.quotation',
            with: [
                'subscription' => $this->subscription,
                'portalUrl' => route('portal.quotations.show', $this->subscription),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.quotation', [
            'subscription' => $this->subscription,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "quotation-{$this->subscription->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
