<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionLine;
use App\Services\DiscountEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->engine = new DiscountEngine;
});

/*
|--------------------------------------------------------------------------
| Helper: create a subscription with lines
|--------------------------------------------------------------------------
*/

function createSubscriptionWithLines(array $lineOverrides = []): Subscription
{
    $customer = Customer::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    foreach ($lineOverrides as $line) {
        SubscriptionLine::factory()->create(array_merge(
            ['subscription_id' => $subscription->id],
            $line,
        ));
    }

    $subscription->load('lines');

    return $subscription;
}

/*
|--------------------------------------------------------------------------
| applicable(): date window
|--------------------------------------------------------------------------
*/

it('excludes expired discounts', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 500],
    ]);

    Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-06-30'),
        'applies_to' => 'subscription',
        'subscription_id' => $subscription->id,
        'usage_limit' => null,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-07-15'));

    expect($result)->toHaveCount(0);
});

it('excludes discounts that have not started yet', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 500],
    ]);

    Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-12-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'subscription',
        'subscription_id' => $subscription->id,
        'usage_limit' => null,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-15'));

    expect($result)->toHaveCount(0);
});

it('includes discounts on the exact start and end dates', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 500],
    ]);

    $discount = Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-10-31'),
        'end_date' => Carbon::parse('2026-10-31'),
        'applies_to' => 'subscription',
        'subscription_id' => $subscription->id,
        'usage_limit' => null,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-31'));

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($discount->id);
});

/*
|--------------------------------------------------------------------------
| applicable(): usage limit
|--------------------------------------------------------------------------
*/

it('excludes discounts that hit their usage limit', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 500],
    ]);

    Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'subscription',
        'subscription_id' => $subscription->id,
        'usage_limit' => 3,
        'times_used' => 3,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-15'));

    expect($result)->toHaveCount(0);
});

it('includes discounts with null usage limit (unlimited)', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 500],
    ]);

    $discount = Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'subscription',
        'subscription_id' => $subscription->id,
        'usage_limit' => null,
        'times_used' => 999,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-15'));

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($discount->id);
});

/*
|--------------------------------------------------------------------------
| applicable(): min_quantity threshold
|--------------------------------------------------------------------------
*/

it('excludes discounts when total quantity is below min_quantity', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 2, 'amount' => 200],
    ]);

    Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'subscription',
        'subscription_id' => $subscription->id,
        'usage_limit' => null,
        'min_quantity' => 10,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-15'));

    expect($result)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| applicable(): min_purchase threshold
|--------------------------------------------------------------------------
*/

it('excludes discounts when total amount is below min_purchase', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 100],
    ]);

    Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'subscription',
        'subscription_id' => $subscription->id,
        'usage_limit' => null,
        'min_quantity' => null,
        'min_purchase' => 500,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-15'));

    expect($result)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| applicable(): scope — product vs subscription
|--------------------------------------------------------------------------
*/

it('matches subscription-scoped discounts by subscription_id', function () {
    $sub1 = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 500],
    ]);
    $sub2 = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 500],
    ]);

    $discount = Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'subscription',
        'subscription_id' => $sub1->id,
        'usage_limit' => null,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    expect($this->engine->applicable($sub1, Carbon::parse('2026-10-15')))->toHaveCount(1);
    expect($this->engine->applicable($sub2, Carbon::parse('2026-10-15')))->toHaveCount(0);
});

it('matches product-scoped discounts when product is in subscription lines', function () {
    $productId = 42;

    $subscription = createSubscriptionWithLines([
        ['product_id' => $productId, 'quantity' => 5, 'amount' => 500],
    ]);

    $discount = Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'product',
        'product_id' => $productId,
        'usage_limit' => null,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-15'));

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($discount->id);
});

it('excludes product-scoped discounts when product is not in subscription lines', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 10, 'quantity' => 5, 'amount' => 500],
    ]);

    Discount::factory()->create([
        'is_active' => true,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'product',
        'product_id' => 99, // not in subscription lines
        'usage_limit' => null,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-15'));

    expect($result)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| applicable(): inactive discounts
|--------------------------------------------------------------------------
*/

it('excludes inactive discounts', function () {
    $subscription = createSubscriptionWithLines([
        ['product_id' => 1, 'quantity' => 5, 'amount' => 500],
    ]);

    Discount::factory()->create([
        'is_active' => false,
        'start_date' => Carbon::parse('2026-01-01'),
        'end_date' => Carbon::parse('2026-12-31'),
        'applies_to' => 'subscription',
        'subscription_id' => $subscription->id,
        'usage_limit' => null,
        'min_quantity' => null,
        'min_purchase' => null,
    ]);

    $result = $this->engine->applicable($subscription, Carbon::parse('2026-10-15'));

    expect($result)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| amountFor(): fixed vs percentage
|--------------------------------------------------------------------------
*/

it('calculates fixed discount capped at subtotal', function () {
    $discount = new Discount(['type' => 'fixed', 'value' => 200]);

    // Normal case: discount < subtotal
    expect($this->engine->amountFor($discount, 500.00, 1))->toBe(200.0);

    // Cap case: discount > subtotal
    expect($this->engine->amountFor($discount, 150.00, 1))->toBe(150.0);

    // Equal case
    expect($this->engine->amountFor($discount, 200.00, 1))->toBe(200.0);
});

it('calculates percentage discount correctly', function () {
    $discount = new Discount(['type' => 'percentage', 'value' => 10]);

    expect($this->engine->amountFor($discount, 500.00, 1))->toBe(50.0);
});

it('rounds percentage discount to two decimals', function () {
    $discount = new Discount(['type' => 'percentage', 'value' => 15]);

    // 33.33 × 15 / 100 = 4.9995 → 5.00
    expect($this->engine->amountFor($discount, 33.33, 1))->toBe(5.0);
});

it('returns zero for unknown discount type', function () {
    $discount = new Discount(['type' => 'bogus', 'value' => 100]);

    expect($this->engine->amountFor($discount, 500.00, 1))->toBe(0.0);
});

it('calculates zero discount for zero subtotal', function () {
    $fixedDiscount = new Discount(['type' => 'fixed', 'value' => 50]);
    $pctDiscount = new Discount(['type' => 'percentage', 'value' => 20]);

    expect($this->engine->amountFor($fixedDiscount, 0.0, 1))->toBe(0.0);
    expect($this->engine->amountFor($pctDiscount, 0.0, 1))->toBe(0.0);
});
