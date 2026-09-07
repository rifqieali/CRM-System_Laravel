<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('renders forgot password page', function (): void {
    $this->get(route('password.request'))->assertOk();
});

it('sends reset link to existing email', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'user@crm.test']);

    $response = $this->post(route('password.email'), ['email' => $user->email]);

    $response->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('does not reveal whether email exists', function (): void {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'nobody@example.test'])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

it('renders reset password form with token', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', $token))->assertOk();
});

it('resets password with valid token', function (): void {
    Event::fake();
    Notification::fake();
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $response = $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertRedirect(route('login'));
    Event::assertDispatched(PasswordReset::class);

    $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
});

it('rejects invalid token', function (): void {
    $user = User::factory()->create();

    $this->from(route('password.reset', 'invalid-token'))->post(route('password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertSessionHasErrors('email');
});
