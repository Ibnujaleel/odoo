<?php

use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('users can authenticate using the login screen', function () {
    Role::create(['name' => 'customer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/portal');

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('authenticated users are sent to their role home', function () {
    Role::create(['name' => 'customer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user);

    $this->get('/dashboard')->assertRedirect('/portal');
    $this->get('/portal')->assertOk()->assertSee('Welcome back');
});

test('admins are sent to the Filament panel', function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user);

    $this->get('/dashboard')->assertRedirect('/admin');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});
