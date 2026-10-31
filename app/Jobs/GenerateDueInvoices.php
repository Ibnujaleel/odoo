<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDueInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(BillingService $billing): void
    {
        Subscription::dueForBilling()
            ->orderBy('id')
            ->chunkById(200, function ($subscriptions) use ($billing) {
                foreach ($subscriptions as $subscription) {
                    $billing->generateFor($subscription);
                }
            });
    }
}
