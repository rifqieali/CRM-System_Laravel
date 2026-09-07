<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('renders login page for guests', function (): void {
    $this->get(route('login'))->assertOk()->assertSee('Masuk');
});

it('redirects authenticated users away from login', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
});

it('logs in valid credentials', function (): void {
    User::factory()->create([
        'email' => 'user@crm.test',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'user@crm.test',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

it('rejects invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'user@crm.test',
        'password' => Hash::make('password'),
    ]);

    $response = $this->from(route('login'))->post(route('login'), [
        'email' => 'user@crm.test',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs out authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

it('requires valid email and password on login', function (): void {
    $this->post(route('login'), [])
        ->assertSessionHasErrors(['email', 'password']);
});
