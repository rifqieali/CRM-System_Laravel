<?php

use App\Models\User;

test('home redirects unauthenticated users to login', function (): void {
    $this->get('/')->assertRedirect(route('login'));
});

test('home redirects authenticated users to dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
});
