<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('renders profile edit page for authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
});

it('blocks guests from profile page', function (): void {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

it('updates profile name and email', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Budi Updated',
        'email' => 'budi-new@example.test',
    ])->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->name)->toBe('Budi Updated')
        ->and($user->email)->toBe('budi-new@example.test');
});

it('clears email_verified_at when email changes', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => 'new@example.test',
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('rejects email used by another user', function (): void {
    User::factory()->create(['email' => 'taken@example.test']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.test',
        ])
        ->assertSessionHasErrors('email');
});

it('updates password with current password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect();

    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
});

it('rejects password update with wrong current password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertSessionHasErrors('current_password');
});
