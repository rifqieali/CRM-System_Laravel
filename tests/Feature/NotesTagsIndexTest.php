<?php

declare(strict_types=1);

use App\Livewire\Notes\Index as NotesIndex;
use App\Livewire\Shared\NoteEditor;
use App\Livewire\Shared\TagSelector;
use App\Livewire\Tags\Index as TagsIndex;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();
});

it('redirects guests from notes index', function (): void {
    $this->get(route('notes.index'))->assertRedirect(route('login'));
});

it('renders notes index for authenticated user', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('notes.index'))
        ->assertOk();
});

it('shows admin all notes across entities', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);
    $deal = Deal::factory()->create(['owner_id' => $sales->id, 'contact_id' => $contact->id, 'company_id' => $company->id]);

    Note::create(['body' => 'A1', 'user_id' => $sales->id, 'noteable_type' => Contact::class, 'noteable_id' => $contact->id]);
    Note::create(['body' => 'A2', 'user_id' => $sales->id, 'noteable_type' => Company::class, 'noteable_id' => $company->id]);
    Note::create(['body' => 'A3', 'user_id' => $sales->id, 'noteable_type' => Deal::class, 'noteable_id' => $deal->id]);

    Livewire::actingAs($admin)
        ->test(NotesIndex::class)
        ->assertSee('A1')
        ->assertSee('A2')
        ->assertSee('A3');
});

it('scopes notes for sales to own and team', function (): void {
    $sales1 = User::where('email', 'sales1@crm.test')->firstOrFail();
    $sales3 = User::where('email', 'sales3@crm.test')->firstOrFail();

    $c1 = Contact::factory()->create(['owner_id' => $sales1->id]);
    $c3 = Contact::factory()->create(['owner_id' => $sales3->id]);

    Note::create(['body' => 'Mine', 'user_id' => $sales1->id, 'noteable_type' => Contact::class, 'noteable_id' => $c1->id]);
    Note::create(['body' => 'Other', 'user_id' => $sales3->id, 'noteable_type' => Contact::class, 'noteable_id' => $c3->id]);

    Livewire::actingAs($sales1)
        ->test(NotesIndex::class)
        ->assertSee('Mine')
        ->assertDontSee('Other');
});

it('searches notes by body', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    Note::create(['body' => 'Discussing pricing details', 'user_id' => $sales->id, 'noteable_type' => Contact::class, 'noteable_id' => $contact->id]);
    Note::create(['body' => 'Follow up next week', 'user_id' => $sales->id, 'noteable_type' => Contact::class, 'noteable_id' => $contact->id]);

    Livewire::actingAs($admin)
        ->test(NotesIndex::class)
        ->set('search', 'pricing')
        ->assertSee('Discussing pricing details')
        ->assertDontSee('Follow up next week');
});

it('filters notes by entity type', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);
    Note::create(['body' => 'ContactNote', 'user_id' => $sales->id, 'noteable_type' => Contact::class, 'noteable_id' => $contact->id]);
    Note::create(['body' => 'CompanyNote', 'user_id' => $sales->id, 'noteable_type' => Company::class, 'noteable_id' => $company->id]);

    Livewire::actingAs($admin)
        ->test(NotesIndex::class)
        ->set('entityType', Contact::class)
        ->assertSee('ContactNote')
        ->assertDontSee('CompanyNote');
});

it('filters notes by owner', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales1 = User::where('email', 'sales1@crm.test')->firstOrFail();
    $sales2 = User::where('email', 'sales2@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales1->id]);
    Note::create(['body' => 'S1Note', 'user_id' => $sales1->id, 'noteable_type' => Contact::class, 'noteable_id' => $contact->id]);
    Note::create(['body' => 'S2Note', 'user_id' => $sales2->id, 'noteable_type' => Contact::class, 'noteable_id' => $contact->id]);

    Livewire::actingAs($admin)
        ->test(NotesIndex::class)
        ->set('ownerId', (string) $sales1->id)
        ->assertSee('S1Note')
        ->assertDontSee('S2Note');
});

it('deletes note when authorized', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $note = Note::create(['body' => 'Delete me', 'user_id' => $sales->id, 'noteable_type' => Contact::class, 'noteable_id' => $contact->id]);

    Livewire::actingAs($admin)
        ->test(NotesIndex::class)
        ->call('delete', $note->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});

it('forbids deleting note not owned by other team', function (): void {
    $sales1 = User::where('email', 'sales1@crm.test')->firstOrFail();
    $sales3 = User::where('email', 'sales3@crm.test')->firstOrFail();

    $other = User::factory()->create();
    $other->assignRole('Sales');
    $other->update(['manager_id' => $sales3->id]);

    $contact = Contact::factory()->create(['owner_id' => $sales1->id]);
    $note = Note::create(['body' => 'Private', 'user_id' => $sales1->id, 'noteable_type' => Contact::class, 'noteable_id' => $contact->id]);

    Livewire::actingAs($other)
        ->test(NotesIndex::class)
        ->call('delete', $note->id);

    $this->assertDatabaseHas('notes', ['id' => $note->id]);
});

it('resets notes filters', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(NotesIndex::class)
        ->set('search', 'foo')
        ->set('entityType', Contact::class)
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('entityType', '');
});

it('rejects note creation for disallowed morph type', function (): void {
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(NoteEditor::class, ['morphType' => Contact::class, 'morphId' => $contact->id])
        ->set('body', 'try')
        ->call('save')
        ->assertHasNoErrors();

    expect(fn () => Note::create([
        'body' => 'bad',
        'user_id' => $sales->id,
        'noteable_type' => User::class,
        'noteable_id' => $sales->id,
    ]))->toThrow(InvalidArgumentException::class);
});

it('blocks notes from non-team sales via NoteEditor', function (): void {
    $sales1 = User::where('email', 'sales1@crm.test')->firstOrFail();
    $sales3 = User::where('email', 'sales3@crm.test')->firstOrFail();

    $other = User::factory()->create();
    $other->assignRole('Sales');
    $other->update(['manager_id' => $sales3->id]);

    $contact = Contact::factory()->create(['owner_id' => $sales1->id]);

    Livewire::actingAs($other)
        ->test(NoteEditor::class, ['morphType' => Contact::class, 'morphId' => $contact->id])
        ->set('body', 'sneaky')
        ->call('save')
        ->assertForbidden();
});

it('redirects guests from tags index', function (): void {
    $this->get(route('tags.index'))->assertRedirect(route('login'));
});

it('renders tags index for authenticated user', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('tags.index'))
        ->assertOk();
});

it('creates a tag', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->call('openCreate')
        ->set('name', 'Hot Lead')
        ->call('save')
        ->assertHasNoErrors();

    $tag = Tag::where('name', 'Hot Lead')->first();
    expect($tag)->not->toBeNull()
        ->and($tag->slug)->toBe('hot-lead');
});

it('edits a tag', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $tag = Tag::factory()->create(['name' => 'Old', 'slug' => 'old']);

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->call('openEdit', $tag->id)
        ->assertSet('name', 'Old')
        ->set('name', 'New')
        ->call('save')
        ->assertHasNoErrors();

    $tag->refresh();
    expect($tag->name)->toBe('New');
});

it('deletes a tag', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $tag = Tag::factory()->create();

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->call('delete', $tag->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});

it('rejects blank tag name', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->call('openCreate')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('rejects invalid color format', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->call('openCreate')
        ->set('name', 'Cool')
        ->set('color', 'not-a-hex')
        ->call('save')
        ->assertHasErrors(['color']);
});

it('appends slug suffix on collision', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    Tag::factory()->create(['name' => 'VIP', 'slug' => 'vip']);

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->call('openCreate')
        ->set('name', 'VIP')
        ->call('save')
        ->assertHasNoErrors();

    expect(Tag::where('slug', 'vip-2')->exists())->toBeTrue();
});

it('sorts tags by name and slug', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    Tag::factory()->create(['name' => 'Beta', 'slug' => 'beta']);
    Tag::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->set('sort', 'name')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['Alpha', 'Beta']);

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->set('sort', 'name')
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder(['Beta', 'Alpha']);
});

it('searches tags by name', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    Tag::factory()->create(['name' => 'Important', 'slug' => 'important']);
    Tag::factory()->create(['name' => 'Casual', 'slug' => 'casual']);

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->set('search', 'important')
        ->assertSee('Important')
        ->assertDontSee('Casual');
});

it('shows tag usage count from contacts/companies/deals', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $tag = Tag::factory()->create(['name' => 'Tracked', 'slug' => 'tracked']);
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);
    $contact->tags()->attach($tag);
    $company->tags()->attach($tag);

    Livewire::actingAs($admin)
        ->test(TagsIndex::class)
        ->assertSee('Tracked')
        ->assertSee('2');
});

it('allows sales to create and attach tag via TagSelector', function (): void {
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(TagSelector::class, ['morphType' => Contact::class, 'morphId' => $contact->id])
        ->set('newTagName', 'Pipeline')
        ->call('createAndAttach')
        ->assertHasNoErrors();

    $tag = Tag::where('name', 'Pipeline')->first();
    expect($tag)->not->toBeNull();
    $this->assertDatabaseHas('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => Contact::class,
        'taggable_id' => $contact->id,
    ]);
});
