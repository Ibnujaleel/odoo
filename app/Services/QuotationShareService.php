<?php

namespace App\Services;

use App\Models\Subscription;

class QuotationShareService
{
    /**
     * Generate a WhatsApp share URL for a given quotation subscription.
     */
    public static function whatsappUrl(Subscription $subscription): string
    {
        $link = route('portal.quotations.show', $subscription);
        $message = "Your Brightloop quotation: {$link}";

        return 'https://wa.me/?text='.rawurlencode($message);
    }
}
