<?php

declare(strict_types=1);

use App\Livewire\Shared\LogActivityModal;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('creates activity attached to contact', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(LogActivityModal::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('type', Activity::TYPE_CALL)
        ->set('subject', 'Follow-up call')
        ->set('description', 'Discussed renewal')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'activityable_type' => Contact::class,
        'activityable_id' => $contact->id,
        'subject' => 'Follow-up call',
        'user_id' => $user->id,
    ]);
});

it('creates activity attached to company', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(LogActivityModal::class, [
            'morphType' => Company::class,
            'morphId' => $company->id,
        ])
        ->set('type', Activity::TYPE_MEETING)
        ->set('subject', 'Q4 review')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'activityable_type' => Company::class,
        'activityable_id' => $company->id,
    ]);
});

it('creates activity attached to deal', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $contact = Contact::factory()->create();
    $company = Company::factory()->create();
    $deal = Deal::factory()->create([
        'contact_id' => $contact->id,
        'company_id' => $company->id,
        'owner_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(LogActivityModal::class, [
            'morphType' => Deal::class,
            'morphId' => $deal->id,
        ])
        ->set('type', Activity::TYPE_EMAIL)
        ->set('subject', 'Sent proposal')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'activityable_type' => Deal::class,
        'activityable_id' => $deal->id,
    ]);
});

it('rejects invalid activity type', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(LogActivityModal::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('type', 'invalid-type')
        ->set('subject', 'X')
        ->call('save')
        ->assertHasErrors(['type']);
});

it('requires subject', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(LogActivityModal::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('subject', '')
        ->call('save')
        ->assertHasErrors(['subject']);
});
