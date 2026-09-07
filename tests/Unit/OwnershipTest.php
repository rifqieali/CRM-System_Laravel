<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\User;
use App\Support\Ownership;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('returns true for admin on any record', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $record = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    expect(Ownership::check($admin, $record))->toBeTrue();
});

it('returns true for manager on any record', function (): void {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');
    $record = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    expect(Ownership::check($manager, $record))->toBeTrue();
});

it('returns true for sales on record they own', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $record = Contact::factory()->create(['owner_id' => $sales->id]);

    expect(Ownership::check($sales, $record))->toBeTrue();
});

it('returns false for sales on record owned by stranger', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $stranger = User::factory()->create();
    $record = Contact::factory()->create(['owner_id' => $stranger->id]);

    expect(Ownership::check($sales, $record))->toBeFalse();
});

it('returns true for sales on teammate record (same manager)', function (): void {
    $manager = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $manager->id]);
    $sales->assignRole('Sales');
    $teammate = User::factory()->create(['manager_id' => $manager->id]);
    $record = Contact::factory()->create(['owner_id' => $teammate->id]);

    expect(Ownership::check($sales, $record))->toBeTrue();
});

it('returns false for sales with no manager on teammate record', function (): void {
    $sales = User::factory()->create(['manager_id' => null]);
    $sales->assignRole('Sales');
    $teammate = User::factory()->create(['manager_id' => null]);
    $record = Contact::factory()->create(['owner_id' => $teammate->id]);

    expect(Ownership::check($sales, $record))->toBeFalse();
});

it('returns false for sales with manager on outsider record', function (): void {
    $managerA = User::factory()->create();
    $managerB = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $managerA->id]);
    $sales->assignRole('Sales');
    $outsider = User::factory()->create(['manager_id' => $managerB->id]);
    $record = Contact::factory()->create(['owner_id' => $outsider->id]);

    expect(Ownership::check($sales, $record))->toBeFalse();
});
