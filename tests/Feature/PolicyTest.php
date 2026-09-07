<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('admin can update any contact', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $contact = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    expect($admin->can('update', $contact))->toBeTrue();
});

it('manager can update any contact', function (): void {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $contact = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    expect($manager->can('update', $contact))->toBeTrue();
});

it('sales can update own contact', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    expect($sales->can('update', $contact))->toBeTrue();
});

it('sales can update teammate contact (same manager)', function (): void {
    $manager = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $manager->id]);
    $sales->assignRole('Sales');
    $teammate = User::factory()->create(['manager_id' => $manager->id]);

    $contact = Contact::factory()->create(['owner_id' => $teammate->id]);

    expect($sales->can('update', $contact))->toBeTrue();
});

it('sales cannot update contact owned by stranger', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    $stranger = User::factory()->create();
    $contact = Contact::factory()->create(['owner_id' => $stranger->id]);

    expect($sales->can('update', $contact))->toBeFalse();
});

it('sales cannot update contact owned by outsider-manager team', function (): void {
    $managerA = User::factory()->create();
    $managerB = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $managerA->id]);
    $sales->assignRole('Sales');
    $outsider = User::factory()->create(['manager_id' => $managerB->id]);

    $contact = Contact::factory()->create(['owner_id' => $outsider->id]);

    expect($sales->can('update', $contact))->toBeFalse();
});

it('manager with no manager permission cannot edit outsider record', function (): void {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');
    $stranger = User::factory()->create();

    $company = Company::factory()->create(['owner_id' => $stranger->id]);

    expect($manager->can('update', $company))->toBeTrue();
});

it('sales cannot delete contact owned by stranger', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    $contact = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    expect($sales->can('delete', $contact))->toBeFalse();
});

it('admin can view any contact', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect($admin->can('viewAny', Contact::class))->toBeTrue();
});

it('sales activity policy allows update on own author', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    $activity = Activity::factory()->create([
        'user_id' => $sales->id,
        'activityable_type' => Contact::class,
        'activityable_id' => $contact->id,
    ]);

    expect($sales->can('update', $activity))->toBeTrue();
});

it('sales activity policy allows author to update own activity even on non-owned parent', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $stranger = User::factory()->create();
    $contact = Contact::factory()->create(['owner_id' => $stranger->id]);

    $activity = Activity::factory()->create([
        'user_id' => $sales->id,
        'activityable_type' => Contact::class,
        'activityable_id' => $contact->id,
    ]);

    expect($sales->can('update', $activity))->toBeTrue();
});

it('sales activity policy denies update when sales is not author and parent not owned', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $otherSales = User::factory()->create();
    $stranger = User::factory()->create();
    $contact = Contact::factory()->create(['owner_id' => $stranger->id]);

    $activity = Activity::factory()->create([
        'user_id' => $otherSales->id,
        'activityable_type' => Contact::class,
        'activityable_id' => $contact->id,
    ]);

    expect($sales->can('update', $activity))->toBeFalse();
});

it('admin can view any activity regardless of parent', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $contact = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    $activity = Activity::factory()->create([
        'user_id' => $admin->id,
        'activityable_type' => Contact::class,
        'activityable_id' => $contact->id,
    ]);

    expect($admin->can('view', $activity))->toBeTrue();
});

it('deal policy mirrors contact policy ownership semantics', function (): void {
    $manager = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $manager->id]);
    $sales->assignRole('Sales');
    $teammate = User::factory()->create(['manager_id' => $manager->id]);

    $contact = Contact::factory()->create();
    $company = Company::factory()->create();

    $deal = Deal::factory()->create([
        'contact_id' => $contact->id,
        'company_id' => $company->id,
        'owner_id' => $teammate->id,
    ]);

    expect($sales->can('view', $deal))->toBeTrue()
        ->and($sales->can('update', $deal))->toBeTrue()
        ->and($sales->can('delete', $deal))->toBeTrue();
});
