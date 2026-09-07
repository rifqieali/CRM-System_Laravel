<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects guests to login', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('renders dashboard for authenticated user with kpi cards', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Selamat datang')
        ->assertSee('Pipeline Value')
        ->assertSee('Activities Due Today')
        ->assertSee('Aktivitas Terbaru');
});

it('shows user name in greeting', function (): void {
    $user = User::factory()->create(['name' => 'Andi']);
    $user->assignRole('Sales');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Andi');
});

it('requires verified email', function (): void {
    $user = User::factory()->unverified()->create();
    $user->assignRole('Sales');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});
