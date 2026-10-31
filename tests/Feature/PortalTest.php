<?php

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;

beforeEach(function () {
    $this->seed(RolesAndUsersSeeder::class);
});

test('customers can open every portal page', function () {
    $user = User::where('email', 'customer@brightloop.test')->firstOrFail();

    $this->actingAs($user);

    foreach (['/portal', '/portal/quotations', '/portal/subscriptions', '/portal/invoices', '/portal/account'] as $url) {
        $this->get($url)->assertOk();
    }
});

test('staff cannot access the customer portal', function () {
    $user = User::where('email', 'staff@brightloop.test')->firstOrFail();

    $this->actingAs($user)->get('/portal')->assertForbidden();
});

test('customers only see their own invoices', function () {
    $user = User::where('email', 'customer@brightloop.test')->firstOrFail();
    $customer = $user->customer;

    $otherCustomer = Customer::factory()->create();
    $otherSubscription = Subscription::factory()->for($otherCustomer)->for(Plan::factory())->create();

    $otherInvoice = Invoice::factory()->for($otherSubscription)->for($otherCustomer)->create([
        'number' => 'INV-OTHER-00001',
        'billing_period_start' => today()->subMonth(),
        'status' => InvoiceStatus::Confirmed,
        'total' => 99,
    ]);

    $this->actingAs($user)
        ->get('/portal/invoices')
        ->assertOk()
        ->assertDontSee($otherInvoice->number);
});

test('customers cannot view another customers quotation', function () {
    $user = User::where('email', 'customer@brightloop.test')->firstOrFail();

    $otherCustomer = Customer::factory()->create();
    $quotation = Subscription::factory()
        ->for($otherCustomer)
        ->for(Plan::factory())
        ->create(['status' => SubscriptionStatus::Quotation]);

    $this->actingAs($user)
        ->get(route('portal.quotations.show', $quotation))
        ->assertForbidden();
});
