<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('The CRM that keeps you in the loop')
        ->assertSee('Quote, bill and collect without the busywork.')
        ->assertSee('href="'.route('home').'"', false)
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('href="'.route('register').'"', false);
});
