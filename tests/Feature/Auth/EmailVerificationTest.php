<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects unverified user to notice page when accessing dashboard', function (): void {
    $user = User::factory()->unverified()->create();
    $user->assignRole('Sales');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

it('renders verification notice', function (): void {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get(route('verification.notice'))->assertOk();
});

it('allows verified user to access dashboard', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('verifies email when valid signed link visited', function (): void {
    Event::fake();

    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

    Event::assertDispatched(Verified::class);
    $this->assertNotNull($user->fresh()->email_verified_at);
});

it('rejects invalid signature', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.verify', ['id' => $user->id, 'hash' => 'invalid']))
        ->assertStatus(403);
});

it('resends verification notification on request', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});
