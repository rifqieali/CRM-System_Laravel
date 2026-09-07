<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('returns empty array when user has no manager', function (): void {
    $user = User::factory()->create(['manager_id' => null]);

    expect($user->teamIds())->toBe([]);
});

it('returns ids of other users sharing the same manager', function (): void {
    $manager = User::factory()->create(['manager_id' => null]);
    $sales1 = User::factory()->create(['manager_id' => $manager->id]);
    $sales2 = User::factory()->create(['manager_id' => $manager->id]);
    $outsider = User::factory()->create(['manager_id' => null]);

    $team = $sales1->teamIds();

    expect($team)->toContain($sales2->id)
        ->and($team)->not->toContain($sales1->id)
        ->and($team)->not->toContain($outsider->id)
        ->and($team)->not->toContain($manager->id);
});

it('exposes owner-scoped contact/company/deal relations', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Contact::factory()->count(2)->create(['owner_id' => $user->id]);
    Contact::factory()->count(3)->create(['owner_id' => $other->id]);

    Company::factory()->count(1)->create(['owner_id' => $user->id]);
    Company::factory()->count(2)->create(['owner_id' => $other->id]);

    expect($user->contacts()->count())->toBe(2)
        ->and($user->companies()->count())->toBe(1)
        ->and($user->deals()->count())->toBe(0);
});

it('exposes manager relation and isAdmin/isManager helpers', function (): void {
    $manager = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $manager->assignRole('Manager');

    $user = User::factory()->create(['manager_id' => $manager->id]);
    $user->assignRole('Sales');

    expect($user->manager->id)->toBe($manager->id)
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->isManager())->toBeFalse()
        ->and($admin->isAdmin())->toBeTrue()
        ->and($manager->isManager())->toBeTrue();
});
