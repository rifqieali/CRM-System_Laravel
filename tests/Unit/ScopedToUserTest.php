<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('returns all records for admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    Contact::factory()->count(3)->create();
    Company::factory()->count(2)->create();

    $totalCompanies = Contact::query()->whereNotNull('company_id')->count() + 2;

    expect(Contact::scopedTo($admin)->count())->toBe(3)
        ->and(Company::scopedTo($admin)->count())->toBe($totalCompanies);
});

it('returns all records for manager', function (): void {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    Contact::factory()->count(3)->create();

    expect(Contact::scopedTo($manager)->count())->toBe(3);
});

it('returns only own records for sales with no manager', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Contact::factory()->count(2)->create(['owner_id' => $sales->id]);
    Contact::factory()->count(3)->create();

    expect(Contact::scopedTo($sales)->count())->toBe(2);
});

it('returns own + teammate records for sales with manager', function (): void {
    $manager = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $manager->id]);
    $sales->assignRole('Sales');
    $teammate = User::factory()->create(['manager_id' => $manager->id]);
    $outsider = User::factory()->create();

    Contact::factory()->count(1)->create(['owner_id' => $sales->id]);
    Contact::factory()->count(2)->create(['owner_id' => $teammate->id]);
    Contact::factory()->count(3)->create(['owner_id' => $outsider->id]);

    expect(Contact::scopedTo($sales)->count())->toBe(3);
});

it('works on deals', function (): void {
    $manager = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $manager->id]);
    $sales->assignRole('Sales');
    $teammate = User::factory()->create(['manager_id' => $manager->id]);

    $contact = Contact::factory()->create();
    $company = Company::factory()->create();

    Deal::factory()->count(1)->create([
        'contact_id' => $contact->id,
        'company_id' => $company->id,
        'owner_id' => $sales->id,
    ]);
    Deal::factory()->count(2)->create([
        'contact_id' => $contact->id,
        'company_id' => $company->id,
        'owner_id' => $teammate->id,
    ]);

    $outsiderDeal = Deal::factory()->create([
        'contact_id' => $contact->id,
        'company_id' => $company->id,
        'owner_id' => User::factory()->create()->id,
    ]);

    expect(Deal::scopedTo($sales)->count())->toBe(3)
        ->and(Deal::scopedTo($sales)->whereKey($outsiderDeal->id)->exists())->toBeFalse();
});
