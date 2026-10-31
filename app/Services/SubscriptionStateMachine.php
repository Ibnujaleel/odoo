<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Validation\ValidationException;

class SubscriptionStateMachine
{
    /** @var array<string, array<int, string>> */
    private const ALLOWED = [
        SubscriptionStatus::Draft->value => [SubscriptionStatus::Quotation->value],
        SubscriptionStatus::Quotation->value => [SubscriptionStatus::Confirmed->value],
        SubscriptionStatus::Confirmed->value => [SubscriptionStatus::Active->value],
        SubscriptionStatus::Active->value => [SubscriptionStatus::Closed->value],
        SubscriptionStatus::Closed->value => [],
    ];

    public function transition(Subscription $s, string $to): Subscription
    {
        $from = $s->status instanceof SubscriptionStatus
            ? $s->status->value
            : (string) $s->status;

        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Subscription cannot move from {$from} to {$to}.",
            ]);
        }

        $status = SubscriptionStatus::from($to);
        $s->status = $status;

        if ($status === SubscriptionStatus::Active && $s->start_date === null) {
            $s->start_date = today();
        }

        if ($status === SubscriptionStatus::Active && $s->next_billing_date === null) {
            $s->next_billing_date = $s->start_date?->copy() ?? today();
        }

        $s->save();

        return $s->refresh();
    }
}
