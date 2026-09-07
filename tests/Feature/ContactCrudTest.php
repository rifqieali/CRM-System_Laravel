<?php

declare(strict_types=1);

use App\Livewire\Contacts\Form as ContactForm;
use App\Livewire\Contacts\Index as ContactsIndex;
use App\Livewire\Contacts\Show as ContactShow;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects guests to login', function (): void {
    $this->get(route('contacts.index'))->assertRedirect(route('login'));
});

it('renders contacts index for authenticated user', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');

    $this->actingAs($user)
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertSee('Contacts');
});

it('lists scoped contacts only', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $stranger = User::factory()->create();

    Contact::factory()->count(3)->create(['owner_id' => $sales->id]);
    Contact::factory()->count(2)->create(['owner_id' => $stranger->id]);

    Livewire::actingAs($sales)
        ->test(ContactsIndex::class)
        ->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 3);
});

it('filters by search term on name and email', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Contact::factory()->create(['owner_id' => $sales->id, 'first_name' => 'Andi', 'email' => 'andi@x.test']);
    Contact::factory()->create(['owner_id' => $sales->id, 'first_name' => 'Budi', 'email' => 'budi@y.test']);

    Livewire::actingAs($sales)
        ->test(ContactsIndex::class)
        ->set('search', 'andi')
        ->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 1);
});

it('admin sees all contacts regardless of owner', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Contact::factory()->count(2)->create(['owner_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(ContactsIndex::class)
        ->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 2);
});

it('sales cannot see contact owned by another team', function (): void {
    $managerA = User::factory()->create();
    $managerB = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $managerA->id]);
    $sales->assignRole('Sales');
    $outsider = User::factory()->create(['manager_id' => $managerB->id]);

    Contact::factory()->count(3)->create(['owner_id' => $outsider->id]);
    Contact::factory()->count(1)->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(ContactsIndex::class)
        ->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 1);
});

it('renders create form for authorized user', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    $this->actingAs($sales)->get(route('contacts.create'))->assertOk();
});

it('creates a contact via Form component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(ContactForm::class)
        ->set('first_name', 'Budi')
        ->set('last_name', 'Santoso')
        ->set('email', 'budi@example.test')
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('contacts.show', ['contact' => 1]));

    $this->assertDatabaseHas('contacts', [
        'email' => 'budi@example.test',
        'first_name' => 'Budi',
        'owner_id' => $sales->id,
    ]);
});

it('validates required fields on create', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(ContactForm::class)
        ->set('first_name', '')
        ->set('last_name', '')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name', 'email']);
});

it('rejects duplicate email on create', function (): void {
    Contact::factory()->create(['email' => 'dup@x.test']);
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(ContactForm::class)
        ->set('first_name', 'X')
        ->set('last_name', 'Y')
        ->set('email', 'dup@x.test')
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasErrors(['email']);
});

it('allows save-and-add-another reset flow', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(ContactForm::class)
        ->set('first_name', 'Andi')
        ->set('last_name', 'Wijaya')
        ->set('email', 'andi@x.test')
        ->set('owner_id', $sales->id)
        ->call('save', addAnother: true)
        ->assertHasNoErrors()
        ->assertSet('first_name', '')
        ->assertSet('email', '');

    $this->assertDatabaseCount('contacts', 1);
});

it('loads existing contact on edit', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create([
        'owner_id' => $sales->id,
        'first_name' => 'Andi',
        'email' => 'andi@x.test',
    ]);

    Livewire::actingAs($sales)
        ->test(ContactForm::class, ['contact' => $contact])
        ->assertSet('contactId', $contact->id)
        ->assertSet('first_name', 'Andi')
        ->assertSet('email', 'andi@x.test');
});

it('updates contact via Form component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(ContactForm::class, ['contact' => $contact])
        ->set('first_name', 'Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($contact->fresh()->first_name)->toBe('Updated');
});

it('denies edit for sales on contact owned by stranger', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    Livewire::actingAs($sales)
        ->test(ContactForm::class, ['contact' => $contact])
        ->assertForbidden();
});

it('shows contact to authorized user', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(ContactShow::class, ['contact' => $contact])
        ->assertSuccessful()
        ->assertSee($contact->first_name);
});

it('forbids show for unauthorized sales', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    $this->actingAs($sales)
        ->get(route('contacts.show', $contact))
        ->assertForbidden();
});

it('deletes contact via Index component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(ContactsIndex::class)
        ->call('delete', $contact->id);

    $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
});

it('rejects delete on contact not owned', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => User::factory()->create()->id]);

    Livewire::actingAs($sales)
        ->test(ContactsIndex::class)
        ->call('delete', $contact->id)
        ->assertForbidden();
});

it('links contact to company', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create();

    Livewire::actingAs($sales)
        ->test(ContactForm::class)
        ->set('first_name', 'Andi')
        ->set('last_name', 'X')
        ->set('email', 'a@x.test')
        ->set('company_id', $company->id)
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('contacts', [
        'email' => 'a@x.test',
        'company_id' => $company->id,
    ]);
});
