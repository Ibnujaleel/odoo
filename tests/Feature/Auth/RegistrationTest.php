<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function () {
    Role::create(['name' => 'customer', 'guard_name' => 'web']);

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'Brightloop@123')
        ->set('password_confirmation', 'Brightloop@123');

    $component->call('register');

    $component->assertRedirect('/portal');

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->hasRole('customer'))->toBeTrue()
        ->and(Customer::where('user_id', $user->id)->where('email', 'test@example.com')->exists())->toBeTrue();
});
