<?php

declare(strict_types=1);

use App\Jobs\GenerateDueInvoices;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionLine;
use App\Models\Tax;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function setupBillingScenario(array $overrides = []): array
{
    $customer = Customer::factory()->create();

    $plan = Plan::factory()->create(array_merge([
        'billing_period' => 'monthly',
        'auto_close'     => false,
        'end_date'       => null,
    ], $overrides['plan'] ?? []));

    $product = Product::factory()->create([
        'name'        => 'Test Product',
        'sales_price' => 100,
        'is_active'   => true,
    ]);

    $subscription = Subscription::factory()->create(array_merge([
        'customer_id'       => $customer->id,
        'plan_id'           => $plan->id,
        'status'            => 'active',
        'next_billing_date' => Carbon::today()->toDateString(),
        'payment_terms'     => 30,
    ], $overrides['subscription'] ?? []));

    $tax = null;
    if (! empty($overrides['tax'])) {
        $tax = Tax::factory()->create($overrides['tax']);
    }

    SubscriptionLine::factory()->create(array_merge([
        'subscription_id' => $subscription->id,
        'product_id'      => $product->id,
        'quantity'        => $overrides['quantity'] ?? 2,
        'unit_price'      => $overrides['unit_price'] ?? 100,
        'tax_id'          => $tax?->id,
        'amount'          => ($overrides['quantity'] ?? 2) * ($overrides['unit_price'] ?? 100),
    ], $overrides['line'] ?? []));

    return compact('customer', 'plan', 'product', 'subscription', 'tax');
}

/*
|--------------------------------------------------------------------------
| Idempotency
|--------------------------------------------------------------------------
*/

it('creates exactly one invoice per billing period (idempotent)', function () {
    ['subscription' => $subscription] = setupBillingScenario();

    $service = app(BillingService::class);

    $invoice1 = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));
    $invoice2 = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($invoice1)->not->toBeNull()
        ->and($invoice2)->not->toBeNull()
        ->and($invoice1->id)->toBe($invoice2->id)
        ->and(Invoice::count())->toBe(1);
});

it('running the job twice yields one invoice per subscription', function () {
    ['subscription' => $subscription] = setupBillingScenario();

    $job1 = new GenerateDueInvoices();
    $job1->handle(app(BillingService::class));

    $job2 = new GenerateDueInvoices();
    $job2->handle(app(BillingService::class));

    expect(Invoice::where('subscription_id', $subscription->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Skipped Subscriptions
|--------------------------------------------------------------------------
*/

it('skips closed subscriptions', function () {
    ['subscription' => $subscription] = setupBillingScenario([
        'subscription' => ['status' => 'closed'],
    ]);

    $service = app(BillingService::class);
    $result  = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($result)->toBeNull()
        ->and(Invoice::count())->toBe(0);
});

it('skips draft subscriptions', function () {
    ['subscription' => $subscription] = setupBillingScenario([
        'subscription' => ['status' => 'draft'],
    ]);

    $service = app(BillingService::class);
    $result  = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($result)->toBeNull()
        ->and(Invoice::count())->toBe(0);
});

it('skips subscriptions whose next_billing_date is in the future', function () {
    ['subscription' => $subscription] = setupBillingScenario([
        'subscription' => ['next_billing_date' => Carbon::tomorrow()->toDateString()],
    ]);

    $service = app(BillingService::class);
    $result  = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($result)->toBeNull()
        ->and(Invoice::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Tax Totals
|--------------------------------------------------------------------------
*/

it('calculates tax totals correctly for percentage tax', function () {
    ['subscription' => $subscription] = setupBillingScenario([
        'tax'        => ['type' => 'percentage', 'rate' => 18, 'is_active' => true, 'name' => 'GST'],
        'quantity'   => 2,
        'unit_price' => 100,
    ]);

    $service = app(BillingService::class);
    $invoice = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->subtotal)->toBe(200.0)
        ->and((float) $invoice->tax_total)->toBe(36.0)   // 200 × 18% = 36
        ->and((float) $invoice->total)->toBe(236.0);
});

it('calculates tax totals correctly for fixed tax', function () {
    ['subscription' => $subscription] = setupBillingScenario([
        'tax'        => ['type' => 'fixed', 'rate' => 25, 'is_active' => true, 'name' => 'Service Fee'],
        'quantity'   => 3,
        'unit_price' => 50,
    ]);

    $service = app(BillingService::class);
    $invoice = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->subtotal)->toBe(150.0)
        ->and((float) $invoice->tax_total)->toBe(25.0)
        ->and((float) $invoice->total)->toBe(175.0);
});

/*
|--------------------------------------------------------------------------
| Discount Usage
|--------------------------------------------------------------------------
*/

it('records discount usage and increments times_used', function () {
    ['subscription' => $subscription, 'product' => $product] = setupBillingScenario([
        'quantity'   => 5,
        'unit_price' => 100,
    ]);

    $discount = Discount::factory()->create([
        'name'            => 'Test Discount',
        'type'            => 'percentage',
        'value'           => 10,
        'is_active'       => true,
        'start_date'      => Carbon::today()->subDay(),
        'end_date'        => Carbon::today()->addMonth(),
        'applies_to'      => 'product',
        'product_id'      => $product->id,
        'usage_limit'     => 5,
        'times_used'      => 0,
        'min_quantity'    => null,
        'min_purchase'    => null,
    ]);

    $service = app(BillingService::class);
    $invoice = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($invoice)->not->toBeNull()
        ->and(DiscountUsage::count())->toBe(1)
        ->and($discount->fresh()->times_used)->toBe(1);
});

it('stops applying discount once usage limit is reached', function () {
    ['subscription' => $subscription, 'product' => $product] = setupBillingScenario([
        'quantity'   => 5,
        'unit_price' => 100,
    ]);

    $discount = Discount::factory()->create([
        'name'            => 'Limited Discount',
        'type'            => 'percentage',
        'value'           => 10,
        'is_active'       => true,
        'start_date'      => Carbon::today()->subDay(),
        'end_date'        => Carbon::today()->addMonth(),
        'applies_to'      => 'product',
        'product_id'      => $product->id,
        'usage_limit'     => 1,
        'times_used'      => 1, // Already exhausted
        'min_quantity'    => null,
        'min_purchase'    => null,
    ]);

    $service = app(BillingService::class);
    $invoice = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->discount_total)->toBe(0.0)
        ->and(DiscountUsage::count())->toBe(0);
});

it('ignores expired discounts', function () {
    ['subscription' => $subscription, 'product' => $product] = setupBillingScenario([
        'quantity'   => 5,
        'unit_price' => 100,
    ]);

    Discount::factory()->create([
        'name'            => 'Old Discount',
        'type'            => 'percentage',
        'value'           => 50,
        'is_active'       => true,
        'start_date'      => Carbon::today()->subYear(),
        'end_date'        => Carbon::today()->subMonth(), // Expired
        'applies_to'      => 'product',
        'product_id'      => $product->id,
        'usage_limit'     => null,
        'times_used'      => 0,
        'min_quantity'    => null,
        'min_purchase'    => null,
    ]);

    $service = app(BillingService::class);
    $invoice = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->discount_total)->toBe(0.0);
});

/*
|--------------------------------------------------------------------------
| Auto-close
|--------------------------------------------------------------------------
*/

it('auto-closes subscription when plan has auto_close and end_date has passed', function () {
    ['subscription' => $subscription] = setupBillingScenario([
        'plan' => [
            'auto_close' => true,
            'end_date'   => Carbon::yesterday()->toDateString(),
        ],
    ]);

    // Mock the SubscriptionStateMachine
    $stateMachine = Mockery::mock(\App\Services\SubscriptionStateMachine::class);
    $stateMachine->shouldReceive('transition')
        ->once()
        ->with(Mockery::type(Subscription::class), 'closed')
        ->andReturnUsing(function ($s) {
            $s->update(['status' => 'closed']);
            return $s;
        });

    app()->instance(\App\Services\SubscriptionStateMachine::class, $stateMachine);

    $service = app(BillingService::class);
    $result  = $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($result)->toBeNull()
        ->and(Invoice::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| next_billing_date advancement
|--------------------------------------------------------------------------
*/

it('advances next_billing_date by the plan billing period', function () {
    $today = Carbon::today();

    ['subscription' => $subscription] = setupBillingScenario([
        'plan'         => ['billing_period' => 'monthly'],
        'subscription' => ['next_billing_date' => $today->toDateString()],
    ]);

    $service = app(BillingService::class);
    $service->generateFor($subscription->fresh(['plan', 'lines.tax', 'lines.product']));

    expect($subscription->fresh()->next_billing_date)
        ->toBe($today->copy()->addMonth()->toDateString());
});

/*
|--------------------------------------------------------------------------
| GenerateDueInvoices Job
|--------------------------------------------------------------------------
*/

it('processes multiple subscriptions and counts results', function () {
    // Create 3 active subscriptions due today
    for ($i = 0; $i < 3; $i++) {
        setupBillingScenario();
    }

    // Create 1 closed subscription (should be skipped)
    setupBillingScenario(['subscription' => ['status' => 'closed']]);

    $job = new GenerateDueInvoices();
    $job->handle(app(BillingService::class));

    expect($job->invoicesCreated)->toBe(3)
        ->and($job->failures)->toBe(0)
        ->and(Invoice::count())->toBe(3);
});
