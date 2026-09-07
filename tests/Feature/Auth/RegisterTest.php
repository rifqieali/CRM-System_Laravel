<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

it('renders registration page', function (): void {
    $this->get(route('register'))->assertOk();
});

it('creates a new user and dispatches Registered event', function (): void {
    Event::fake();

    $response = $this->post(route('register'), [
        'name' => 'Budi',
        'email' => 'budi@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertDatabaseHas('users', ['email' => 'budi@example.test']);
    $this->assertAuthenticated();

    Event::assertDispatched(Registered::class);
});

it('rejects duplicate email', function (): void {
    User::factory()->create(['email' => 'budi@example.test']);

    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'Budi',
        'email' => 'budi@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('email');
});

it('rejects mismatched password confirmation', function (): void {
    $response = $this->from(route('register'))->post(route('register'), [
        'name' => 'Budi',
        'email' => 'budi@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Different!',
    ]);

    $response->assertSessionHasErrors('password');
});
