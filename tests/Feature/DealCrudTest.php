<?php

declare(strict_types=1);

use App\Livewire\Deals\Form as DealForm;
use App\Livewire\Deals\Index as DealsIndex;
use App\Livewire\Deals\Show as DealShow;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects guests to login', function (): void {
    $this->get(route('deals.index'))->assertRedirect(route('login'));
});

it('renders deals index for authenticated user', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    $this->actingAs($sales)
        ->get(route('deals.index'))
        ->assertOk()
        ->assertSee('Deals');
});

it('lists scoped deals only', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $stranger = User::factory()->create();
    $contact = Contact::factory();
    $company = Company::factory();

    Deal::factory()->count(3)->create(['owner_id' => $sales->id, 'contact_id' => $contact, 'company_id' => $company]);
    Deal::factory()->count(2)->create(['owner_id' => $stranger->id, 'contact_id' => $contact, 'company_id' => $company]);

    Livewire::actingAs($sales)
        ->test(DealsIndex::class)
        ->assertViewHas('deals', fn ($deals) => $deals->total() === 3);
});

it('filters by name search', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory();
    $company = Company::factory();

    Deal::factory()->create(['owner_id' => $sales->id, 'name' => 'Q4 Renewal', 'contact_id' => $contact, 'company_id' => $company]);
    Deal::factory()->create(['owner_id' => $sales->id, 'name' => 'New Logo', 'contact_id' => $contact, 'company_id' => $company]);

    Livewire::actingAs($sales)
        ->test(DealsIndex::class)
        ->set('search', 'Q4')
        ->assertViewHas('deals', fn ($deals) => $deals->total() === 1);
});

it('filters by stage', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory();
    $company = Company::factory();

    Deal::factory()->create(['owner_id' => $sales->id, 'stage' => 'prospecting', 'contact_id' => $contact, 'company_id' => $company]);
    Deal::factory()->create(['owner_id' => $sales->id, 'stage' => 'won', 'contact_id' => $contact, 'company_id' => $company]);

    Livewire::actingAs($sales)
        ->test(DealsIndex::class)
        ->set('stage', 'won')
        ->assertViewHas('deals', fn ($deals) => $deals->total() === 1);
});

it('admin sees all deals', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory();
    $company = Company::factory();

    Deal::factory()->count(4)->create(['owner_id' => $sales->id, 'contact_id' => $contact, 'company_id' => $company]);

    Livewire::actingAs($admin)
        ->test(DealsIndex::class)
        ->assertViewHas('deals', fn ($deals) => $deals->total() === 4);
});

it('sales cannot see deal owned by outsider', function (): void {
    $managerA = User::factory()->create();
    $managerB = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $managerA->id]);
    $sales->assignRole('Sales');
    $outsider = User::factory()->create(['manager_id' => $managerB->id]);
    $contact = Contact::factory();
    $company = Company::factory();

    Deal::factory()->count(3)->create(['owner_id' => $outsider->id, 'contact_id' => $contact, 'company_id' => $company]);
    Deal::factory()->count(1)->create(['owner_id' => $sales->id, 'contact_id' => $contact, 'company_id' => $company]);

    Livewire::actingAs($sales)
        ->test(DealsIndex::class)
        ->assertViewHas('deals', fn ($deals) => $deals->total() === 1);
});

it('creates a deal via Form component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(DealForm::class)
        ->set('name', 'Q4 Renewal')
        ->set('value', 50000000)
        ->set('stage', 'prospecting')
        ->set('contact_id', $contact->id)
        ->set('company_id', $company->id)
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('deals.show', ['deal' => 1]));

    $this->assertDatabaseHas('deals', [
        'name' => 'Q4 Renewal',
        'stage' => 'prospecting',
        'contact_id' => $contact->id,
        'company_id' => $company->id,
        'owner_id' => $sales->id,
    ]);
});

it('validates required name and value on create', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(DealForm::class)
        ->set('name', '')
        ->set('value', '')
        ->set('currency', '')
        ->set('stage', '')
        ->set('contact_id', $contact->id)
        ->set('company_id', $company->id)
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasErrors(['name', 'value', 'currency', 'stage']);
});

it('validates stage enum', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(DealForm::class)
        ->set('name', 'X')
        ->set('value', 1000)
        ->set('stage', 'invalid')
        ->set('contact_id', $contact->id)
        ->set('company_id', $company->id)
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasErrors(['stage']);
});

it('validates probability range', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(DealForm::class)
        ->set('name', 'X')
        ->set('value', 1000)
        ->set('stage', 'prospecting')
        ->set('probability', 150)
        ->set('contact_id', $contact->id)
        ->set('company_id', $company->id)
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasErrors(['probability']);
});

it('requires contact and company', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(DealForm::class)
        ->set('name', 'X')
        ->set('value', 1000)
        ->set('contact_id', 0)
        ->set('company_id', 0)
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasErrors(['contact_id', 'company_id']);
});

it('loads existing deal on edit', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create([
        'owner_id' => $sales->id,
        'name' => 'Q4 Renewal',
        'stage' => 'negotiation',
    ]);

    Livewire::actingAs($sales)
        ->test(DealForm::class, ['deal' => $deal])
        ->assertSet('dealId', $deal->id)
        ->assertSet('name', 'Q4 Renewal')
        ->assertSet('stage', 'negotiation');
});

it('updates deal via Form component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(DealForm::class, ['deal' => $deal])
        ->set('name', 'Updated Deal')
        ->call('save')
        ->assertHasNoErrors();

    expect($deal->fresh()->name)->toBe('Updated Deal');
});

it('auto-sets closed_at when stage becomes won', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create(['owner_id' => $sales->id, 'stage' => 'prospecting', 'closed_at' => null]);

    Livewire::actingAs($sales)
        ->test(DealForm::class, ['deal' => $deal])
        ->set('stage', 'won')
        ->call('save')
        ->assertHasNoErrors();

    $deal->refresh();
    expect($deal->stage)->toBe('won')
        ->and($deal->closed_at)->not->toBeNull();
});

it('clears closed_at when stage leaves won/lost', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create(['owner_id' => $sales->id, 'stage' => 'won', 'closed_at' => now()]);

    Livewire::actingAs($sales)
        ->test(DealForm::class, ['deal' => $deal])
        ->set('stage', 'negotiation')
        ->call('save')
        ->assertHasNoErrors();

    $deal->refresh();
    expect($deal->closed_at)->toBeNull();
});

it('allows save-and-add-another reset flow', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(DealForm::class)
        ->set('name', 'First Deal')
        ->set('value', 1000)
        ->set('contact_id', $contact->id)
        ->set('company_id', $company->id)
        ->set('owner_id', $sales->id)
        ->call('save', addAnother: true)
        ->assertHasNoErrors()
        ->assertSet('name', '');

    $this->assertDatabaseCount('deals', 1);
});

it('denies edit for unauthorized sales', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create(['owner_id' => User::factory()->create()->id]);

    Livewire::actingAs($sales)
        ->test(DealForm::class, ['deal' => $deal])
        ->assertForbidden();
});

it('shows deal to authorized user', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create(['owner_id' => $sales->id, 'name' => 'Q4 Renewal']);

    Livewire::actingAs($sales)
        ->test(DealShow::class, ['deal' => $deal])
        ->assertSuccessful()
        ->assertSee('Q4 Renewal');
});

it('forbids show for unauthorized sales', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create(['owner_id' => User::factory()->create()->id]);

    Livewire::actingAs($sales)
        ->test(DealShow::class, ['deal' => $deal])
        ->assertForbidden();
});

it('deletes deal via Index component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(DealsIndex::class)
        ->call('delete', $deal->id);

    $this->assertSoftDeleted('deals', ['id' => $deal->id]);
});

it('rejects delete on deal not owned', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $deal = Deal::factory()->create(['owner_id' => User::factory()->create()->id]);

    Livewire::actingAs($sales)
        ->test(DealsIndex::class)
        ->call('delete', $deal->id)
        ->assertForbidden();
});
