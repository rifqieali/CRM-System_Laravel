<?php

declare(strict_types=1);

use App\Livewire\Shared\NoteEditor;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('creates note attached to contact', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(NoteEditor::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('body', 'Met at conference.')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('notes', [
        'noteable_type' => Contact::class,
        'noteable_id' => $contact->id,
        'body' => 'Met at conference.',
        'user_id' => $user->id,
    ]);
});

it('creates note attached to company', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(NoteEditor::class, [
            'morphType' => Company::class,
            'morphId' => $company->id,
        ])
        ->set('body', 'Headcount 50+')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('notes', [
        'noteable_type' => Company::class,
        'noteable_id' => $company->id,
    ]);
});

it('creates note attached to deal', function (): void {
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
        ->test(NoteEditor::class, [
            'morphType' => Deal::class,
            'morphId' => $deal->id,
        ])
        ->set('body', 'Negotiation ongoing')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('notes', [
        'noteable_type' => Deal::class,
        'noteable_id' => $deal->id,
    ]);
});

it('clears body after save', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(NoteEditor::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('body', 'First note.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('body', '');

    $this->assertDatabaseCount('notes', 1);
});

it('requires body', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(NoteEditor::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('body', '')
        ->call('save')
        ->assertHasErrors(['body']);
});
