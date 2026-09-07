<?php

declare(strict_types=1);

use App\Livewire\Shared\TagSelector;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('attaches existing tag to contact', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $tag = Tag::factory()->create(['name' => 'VIP', 'slug' => 'vip']);

    Livewire::actingAs($sales)
        ->test(TagSelector::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->call('toggle', $tag->id);

    $this->assertDatabaseHas('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => Contact::class,
        'taggable_id' => $contact->id,
    ]);
});

it('detaches tag on second toggle', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $tag = Tag::factory()->create();

    Livewire::actingAs($sales)
        ->test(TagSelector::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->call('toggle', $tag->id)
        ->call('toggle', $tag->id);

    $this->assertDatabaseMissing('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => Contact::class,
        'taggable_id' => $contact->id,
    ]);
});

it('creates tag inline and attaches it', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(TagSelector::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('newTagName', 'Hot Lead')
        ->call('createAndAttach')
        ->assertHasNoErrors();

    $tag = Tag::where('name', 'Hot Lead')->first();
    expect($tag)->not->toBeNull()
        ->and($tag->slug)->toBe('hot-lead');

    $this->assertDatabaseHas('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => Contact::class,
        'taggable_id' => $contact->id,
    ]);
});

it('appends suffix when slug collides on inline create', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    Tag::factory()->create(['name' => 'Hot Lead', 'slug' => 'hot-lead']);

    Livewire::actingAs($sales)
        ->test(TagSelector::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('newTagName', 'Hot Lead')
        ->call('createAndAttach')
        ->assertHasNoErrors();

    expect(Tag::where('slug', 'hot-lead-2')->exists())->toBeTrue();
});

it('rejects blank tag name', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(TagSelector::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->set('newTagName', '')
        ->call('createAndAttach')
        ->assertHasErrors(['newTagName']);
});

it('requires update permission to attach tag', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);
    $tag = Tag::factory()->create();

    Livewire::actingAs($sales)
        ->test(TagSelector::class, [
            'morphType' => Contact::class,
            'morphId' => $contact->id,
        ])
        ->call('toggle', $tag->id)
        ->assertForbidden();
});
