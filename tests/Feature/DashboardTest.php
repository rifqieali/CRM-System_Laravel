<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;

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
        ->assertSee('Selamat')
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

it('shows deals closing this week callout when present', function (): void {
    Carbon::setTestNow('2026-09-09 10:00:00');

    $user = User::factory()->create();
    $user->assignRole('Sales');
    Deal::factory()->create([
        'owner_id' => $user->id,
        'stage' => 'negotiation',
        'expected_close_date' => '2026-09-11',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('1')
        ->assertSee('deals yang closing minggu ini');

    Carbon::setTestNow();
});

it('shows neutral callout when no deals closing this week', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tidak ada deal yang closing minggu ini');
});

it('scopes recent activities to user', function (): void {
    $sales1 = User::factory()->create();
    $sales1->assignRole('Sales');
    $sales2 = User::factory()->create();
    $sales2->assignRole('Sales');

    $c1 = Contact::factory()->create(['owner_id' => $sales1->id]);
    $c2 = Contact::factory()->create(['owner_id' => $sales2->id]);

    $c1->activities()->create(['type' => 'call', 'subject' => 'MineRecent', 'user_id' => $sales1->id]);
    $c2->activities()->create(['type' => 'call', 'subject' => 'OtherRecent', 'user_id' => $sales2->id]);

    $this->actingAs($sales1)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('MineRecent')
        ->assertDontSee('OtherRecent');
});

it('requires verified email', function (): void {
    $user = User::factory()->unverified()->create();
    $user->assignRole('Sales');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});
