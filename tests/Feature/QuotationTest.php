<?php

use App\Enums\DiscountType;
use App\Enums\SubscriptionStatus;
use App\Enums\TaxType;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subscription;
use App\Models\SubscriptionLine;
use App\Models\Tax;
use App\Models\User;
use App\Services\QuotationCalculator;
use App\Services\QuotationShareService;
use Database\Seeders\RolesAndUsersSeeder;

beforeEach(function () {
    $this->seed(RolesAndUsersSeeder::class);
});

test('quotation calculator calculates correct line amounts, subtotal, and grand total', function () {
    $tax = Tax::factory()->create([
        'name' => 'VAT 10%',
        'type' => TaxType::Percentage,
        'rate' => 10,
        'is_active' => true,
    ]);

    $lines = [
        [
            'sales_price' => 100.0,
            'quantity' => 2,
            'tax' => $tax,
        ],
        [
            'sales_price' => 50.0,
            'quantity' => 1,
            'tax' => $tax,
        ],
    ];

    $calculator = new QuotationCalculator();
    $result = $calculator->calculate($lines);

    // Line 1: 100 * 2 = 200, Tax = 20
    // Line 2: 50 * 1 = 50, Tax = 5
    // Subtotal = 250, Tax = 25, Total = 275
    expect($result['subtotal'])->toBe(250.0)
        ->and($result['tax'])->toBe(25.0)
        ->and($result['grand_total'])->toBe(275.0)
        ->and($result['lines'])->toHaveCount(2)
        ->and($result['lines'][0]['amount'])->toBe(200.0)
        ->and($result['lines'][1]['amount'])->toBe(50.0);
});

test('quotation calculator adjusts unit price for product variants', function () {
    $product = Product::factory()->create([
        'sales_price' => 200.0,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'attribute' => 'tier',
        'value' => 'enterprise',
        'extra_price' => 75.0,
    ]);

    $lines = [
        [
            'product' => $product,
            'variant' => $variant,
            'quantity' => 2,
        ],
    ];

    $calculator = new QuotationCalculator();
    $result = $calculator->calculate($lines);

    // unit_price = 200 + 75 = 275
    // amount = 2 * 275 = 550
    expect($result['lines'][0]['unit_price'])->toBe(275.0)
        ->and($result['lines'][0]['amount'])->toBe(550.0)
        ->and($result['subtotal'])->toBe(550.0)
        ->and($result['grand_total'])->toBe(550.0);
});

test('quotation calculator applies discounts accurately', function () {
    $discount = Discount::factory()->create([
        'type' => DiscountType::Fixed,
        'value' => 50.0,
        'min_purchase' => 100.0,
        'min_quantity' => 1,
        'is_active' => true,
    ]);

    $lines = [
        [
            'sales_price' => 150.0,
            'quantity' => 1,
        ],
    ];

    $calculator = new QuotationCalculator();
    $result = $calculator->calculate($lines, null, $discount);

    expect($result['subtotal'])->toBe(150.0)
        ->and($result['discount'])->toBe(50.0)
        ->and($result['grand_total'])->toBe(100.0);
});

test('quotation calculator rejects zero or negative quantity', function () {
    $lines = [
        [
            'sales_price' => 100.0,
            'quantity' => 0,
        ],
    ];

    $calculator = new QuotationCalculator();
    $calculator->calculate($lines);
})->throws(InvalidArgumentException::class, 'Line quantity must be greater than zero.');

test('quotation share service generates valid whatsapp url', function () {
    $customer = Customer::where('email', 'customer@brightloop.test')->firstOrFail();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Quotation,
    ]);

    $url = QuotationShareService::whatsappUrl($subscription);

    expect($url)
        ->toStartWith('https://wa.me/?text=')
        ->toContain(rawurlencode(route('portal.quotations.show', $subscription)));
});

test('quotation pdf route is accessible by staff and owning customer, but forbidden to others', function () {
    $customerA = Customer::where('email', 'customer@brightloop.test')->firstOrFail();
    $userA = $customerA->user;

    $otherUser = User::factory()->create();
    $otherUser->syncRoles(['customer']);
    $customerB = Customer::factory()->create(['user_id' => $otherUser->id]);

    $staffUser = User::where('email', 'staff@brightloop.test')->firstOrFail();

    $plan = Plan::factory()->create();
    $product = Product::factory()->create(['sales_price' => 100]);

    $subscription = Subscription::factory()->create([
        'customer_id' => $customerA->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Quotation,
    ]);

    SubscriptionLine::factory()->create([
        'subscription_id' => $subscription->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 100,
        'amount' => 100,
    ]);

    // 1. Staff can view PDF
    $this->actingAs($staffUser)
        ->get(route('portal.quotations.pdf', $subscription))
        ->assertOk();

    // 2. Owning customer can view PDF
    $this->actingAs($userA)
        ->get(route('portal.quotations.pdf', $subscription))
        ->assertOk();

    // 3. Other customer cannot view PDF
    $this->actingAs($otherUser)
        ->get(route('portal.quotations.pdf', $subscription))
        ->assertForbidden();
});
