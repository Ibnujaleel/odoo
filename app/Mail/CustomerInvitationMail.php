<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Customer $customer) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activate Your Brightloop Customer Portal Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.customer-invitation',
            with: [
                'customer' => $this->customer,
                'activationUrl' => url('/activate/'.$this->customer->invite_token),
            ],
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}

