<?php

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Livewire\Portal\ActivateAccount;
use App\Livewire\Portal\QuotationShow;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndUsersSeeder::class);
});

test('customer A cannot open customer B quotation', function () {
    $customerA = Customer::where('email', 'customer@brightloop.test')->firstOrFail();
    $userA = $customerA->user;

    $userB = User::factory()->create();
    $userB->syncRoles(['customer']);
    $customerB = Customer::factory()->create(['user_id' => $userB->id]);

    $plan = Plan::factory()->create();
    $quotationB = Subscription::factory()
        ->for($customerB)
        ->for($plan)
        ->create(['status' => SubscriptionStatus::Quotation]);

    // Customer A attempting to access Customer B's quotation -> 403 Forbidden
    $this->actingAs($userA)
        ->get(route('portal.quotations.show', $quotationB))
        ->assertForbidden();
});

test('customer A cannot open customer B invoice', function () {
    $customerA = Customer::where('email', 'customer@brightloop.test')->firstOrFail();
    $userA = $customerA->user;

    $userB = User::factory()->create();
    $userB->syncRoles(['customer']);
    $customerB = Customer::factory()->create(['user_id' => $userB->id]);

    $plan = Plan::factory()->create();
    $subscriptionB = Subscription::factory()->for($customerB)->for($plan)->create();

    $invoiceB = Invoice::factory()->for($subscriptionB)->for($customerB)->create([
        'number' => 'INV-ISOLATION-001',
        'status' => InvoiceStatus::Confirmed,
    ]);

    // Invoice list only shows own invoices
    $this->actingAs($userA)
        ->get(route('portal.invoices'))
        ->assertOk()
        ->assertDontSee($invoiceB->number);
});

test('customer A cannot see customer B subscription in list', function () {
    $customerA = Customer::where('email', 'customer@brightloop.test')->firstOrFail();
    $userA = $customerA->user;

    $userB = User::factory()->create();
    $userB->syncRoles(['customer']);
    $customerB = Customer::factory()->create(['user_id' => $userB->id]);

    $plan = Plan::factory()->create();
    $subscriptionB = Subscription::factory()->for($customerB)->for($plan)->create([
        'number' => 'SUB-PRIVATE-99999',
        'status' => SubscriptionStatus::Active,
    ]);

    $this->actingAs($userA)
        ->get(route('portal.subscriptions'))
        ->assertOk()
        ->assertDontSee($subscriptionB->number);
});

test('expired quotation cannot be accepted', function () {
    $customer = Customer::where('email', 'customer@brightloop.test')->firstOrFail();
    $user = $customer->user;
    $plan = Plan::factory()->create();

    $expiredQuotation = Subscription::factory()->for($customer)->for($plan)->create([
        'status' => SubscriptionStatus::Quotation,
        'quotation_valid_until' => today()->subDays(5),
    ]);

    $this->actingAs($user);

    Livewire::test(QuotationShow::class, ['subscription' => $expiredQuotation])
        ->call('accept')
        ->assertHasErrors(['status']);

    expect($expiredQuotation->fresh()->status)->toBe(SubscriptionStatus::Quotation);
});

test('customer can accept quotation and activate subscription through payment step', function () {
    $customer = Customer::where('email', 'customer@brightloop.test')->firstOrFail();
    $user = $customer->user;
    $plan = Plan::factory()->create();

    $validQuotation = Subscription::factory()->for($customer)->for($plan)->create([
        'status' => SubscriptionStatus::Quotation,
        'quotation_valid_until' => today()->addDays(7),
    ]);

    $this->actingAs($user);

    // 1. Accept quotation -> status becomes confirmed
    Livewire::test(QuotationShow::class, ['subscription' => $validQuotation])
        ->call('accept')
        ->assertHasNoErrors()
        ->assertSet('showPaymentStep', true);

    expect($validQuotation->fresh()->status)->toBe(SubscriptionStatus::Confirmed);

    // 2. Confirm payment & activate -> status becomes active
    Livewire::test(QuotationShow::class, ['subscription' => $validQuotation->fresh()])
        ->set('paymentMethod', 'card')
        ->call('confirmPaymentAndActivate')
        ->assertHasNoErrors()
        ->assertSet('paymentSuccess', true);

    expect($validQuotation->fresh()->status)->toBe(SubscriptionStatus::Active);
});

test('invitation activation sets password, links user and clears token', function () {
    $token = Str::random(40);

    $customer = Customer::factory()->create([
        'email' => 'newcustomer@example.com',
        'name' => 'New Customer',
        'user_id' => null,
        'invite_token' => $token,
    ]);

    Livewire::test(ActivateAccount::class, ['token' => $token])
        ->set('password', 'Secret@Password123!')
        ->set('password_confirmation', 'Secret@Password123!')
        ->call('activate')
        ->assertHasNoErrors()
        ->assertRedirect(route('portal.dashboard'));

    $customer->refresh();
    expect($customer->invite_token)->toBeNull()
        ->and($customer->user_id)->not->toBeNull()
        ->and($customer->user->hasRole('customer'))->toBeTrue();
});

