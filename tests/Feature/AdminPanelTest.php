<?php

use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;

beforeEach(function () {
    $this->seed(RolesAndUsersSeeder::class);
});

test('admins can open the panel and admin-only resources', function () {
    $admin = User::where('email', 'admin@brightloop.test')->firstOrFail();

    $this->actingAs($admin);

    foreach ([
        '/admin',
        '/admin/customers',
        '/admin/subscriptions',
        '/admin/invoices',
        '/admin/payments',
        '/admin/products',
        '/admin/plans',
        '/admin/taxes',
        '/admin/discounts',
        '/admin/staff',
        '/admin/finance-page',
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

test('staff can open operational resources but not admin-only ones', function () {
    $staff = User::where('email', 'staff@brightloop.test')->firstOrFail();

    $this->actingAs($staff);

    $this->get('/admin')->assertOk();
    $this->get('/admin/customers')->assertOk();
    $this->get('/admin/subscriptions')->assertOk();
    $this->get('/admin/invoices')->assertOk();
    $this->get('/admin/staff')->assertForbidden();
    $this->get('/admin/taxes')->assertForbidden();
    $this->get('/admin/discounts')->assertForbidden();
});

test('customers cannot access the admin panel', function () {
    $customer = User::where('email', 'customer@brightloop.test')->firstOrFail();

    $this->actingAs($customer)
        ->get('/admin')
        ->assertForbidden();
});
