<?php

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function subscriptionWithStatus(SubscriptionStatus $status): Subscription
{
    return Subscription::factory()->create([
        'status' => $status,
        'customer_id' => Customer::factory(),
        'plan_id' => Plan::factory(),
    ]);
}

it('moves a subscription through every valid stage in order', function () {
    $subscription = subscriptionWithStatus(SubscriptionStatus::Draft);
    $machine = app(SubscriptionStateMachine::class);

    foreach ([
        SubscriptionStatus::Quotation,
        SubscriptionStatus::Confirmed,
        SubscriptionStatus::Active,
        SubscriptionStatus::Closed,
    ] as $next) {
        $subscription = $machine->transition($subscription, $next->value);
        expect($subscription->status)->toBe($next);
    }
});

it('rejects every transition that skips or reverses a stage', function (string $from, string $to) {
    $subscription = subscriptionWithStatus(SubscriptionStatus::from($from));

    expect(fn () => app(SubscriptionStateMachine::class)->transition($subscription, $to))
        ->toThrow(ValidationException::class);
})->with([
    ['draft', 'confirmed'],
    ['draft', 'active'],
    ['quotation', 'active'],
    ['confirmed', 'closed'],
    ['active', 'confirmed'],
    ['closed', 'active'],
]);
