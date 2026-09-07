<?php

declare(strict_types=1);

use App\Livewire\Companies\Form as CompanyForm;
use App\Livewire\Companies\Index as CompaniesIndex;
use App\Livewire\Companies\Show as CompanyShow;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects guests to login', function (): void {
    $this->get(route('companies.index'))->assertRedirect(route('login'));
});

it('renders companies index for authenticated user', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    $this->actingAs($sales)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Companies');
});

it('lists scoped companies only', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $stranger = User::factory()->create();

    Company::factory()->count(3)->create(['owner_id' => $sales->id]);
    Company::factory()->count(2)->create(['owner_id' => $stranger->id]);

    Livewire::actingAs($sales)
        ->test(CompaniesIndex::class)
        ->assertViewHas('companies', fn ($companies) => $companies->total() === 3);
});

it('filters by name search', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Company::factory()->create(['owner_id' => $sales->id, 'name' => 'Acme Corp']);
    Company::factory()->create(['owner_id' => $sales->id, 'name' => 'Globex']);

    Livewire::actingAs($sales)
        ->test(CompaniesIndex::class)
        ->set('search', 'Acme')
        ->assertViewHas('companies', fn ($companies) => $companies->total() === 1);
});

it('filters by industry', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Company::factory()->create(['owner_id' => $sales->id, 'industry' => 'tech']);
    Company::factory()->create(['owner_id' => $sales->id, 'industry' => 'finance']);

    Livewire::actingAs($sales)
        ->test(CompaniesIndex::class)
        ->set('industry', 'tech')
        ->assertViewHas('companies', fn ($companies) => $companies->total() === 1);
});

it('admin sees all companies', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Company::factory()->count(4)->create(['owner_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(CompaniesIndex::class)
        ->assertViewHas('companies', fn ($companies) => $companies->total() === 4);
});

it('sales cannot see company owned by outsider', function (): void {
    $managerA = User::factory()->create();
    $managerB = User::factory()->create();
    $sales = User::factory()->create(['manager_id' => $managerA->id]);
    $sales->assignRole('Sales');
    $outsider = User::factory()->create(['manager_id' => $managerB->id]);

    Company::factory()->count(3)->create(['owner_id' => $outsider->id]);
    Company::factory()->count(1)->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(CompaniesIndex::class)
        ->assertViewHas('companies', fn ($companies) => $companies->total() === 1);
});

it('creates a company via Form component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(CompanyForm::class)
        ->set('name', 'Acme Corp')
        ->set('industry', 'tech')
        ->set('size', '51-200')
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('companies.show', ['company' => 1]));

    $this->assertDatabaseHas('companies', [
        'name' => 'Acme Corp',
        'industry' => 'tech',
        'size' => '51-200',
        'owner_id' => $sales->id,
    ]);
});

it('validates required name on create', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(CompanyForm::class)
        ->set('name', '')
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasErrors(['name']);
});

it('validates industry enum', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(CompanyForm::class)
        ->set('name', 'X')
        ->set('industry', 'invalid-industry')
        ->set('owner_id', $sales->id)
        ->call('save')
        ->assertHasErrors(['industry']);
});

// Website format is not enforced at the validation layer (Core MVP); user can correct in UI.
// Replaced by string-length validation via max:255.

it('loads existing company on edit', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create([
        'owner_id' => $sales->id,
        'name' => 'Acme Corp',
        'industry' => 'tech',
    ]);

    Livewire::actingAs($sales)
        ->test(CompanyForm::class, ['company' => $company])
        ->assertSet('companyId', $company->id)
        ->assertSet('name', 'Acme Corp')
        ->assertSet('industry', 'tech');
});

it('updates company via Form component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(CompanyForm::class, ['company' => $company])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($company->fresh()->name)->toBe('Updated Name');
});

it('allows save-and-add-another reset flow', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');

    Livewire::actingAs($sales)
        ->test(CompanyForm::class)
        ->set('name', 'Acme')
        ->set('owner_id', $sales->id)
        ->call('save', addAnother: true)
        ->assertHasNoErrors()
        ->assertSet('name', '');

    $this->assertDatabaseCount('companies', 1);
});

it('denies edit for unauthorized sales', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => User::factory()->create()->id]);

    Livewire::actingAs($sales)
        ->test(CompanyForm::class, ['company' => $company])
        ->assertForbidden();
});

it('shows company to authorized user', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => $sales->id, 'name' => 'Acme Corp']);

    Livewire::actingAs($sales)
        ->test(CompanyShow::class, ['company' => $company])
        ->assertSuccessful()
        ->assertSee('Acme Corp');
});

it('forbids show for unauthorized sales', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => User::factory()->create()->id]);

    Livewire::actingAs($sales)
        ->test(CompanyShow::class, ['company' => $company])
        ->assertForbidden();
});

it('shows related contacts tab content', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => $sales->id]);
    $contact = Contact::factory()->create([
        'owner_id' => $sales->id,
        'company_id' => $company->id,
        'first_name' => 'Budi',
    ]);

    Livewire::actingAs($sales)
        ->test(CompanyShow::class, ['company' => $company])
        ->call('setTab', 'contacts')
        ->assertSee('Budi');
});

it('deletes company via Index component', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($sales)
        ->test(CompaniesIndex::class)
        ->call('delete', $company->id);

    $this->assertSoftDeleted('companies', ['id' => $company->id]);
});

it('rejects delete on company not owned', function (): void {
    $sales = User::factory()->create();
    $sales->assignRole('Sales');
    $company = Company::factory()->create(['owner_id' => User::factory()->create()->id]);

    Livewire::actingAs($sales)
        ->test(CompaniesIndex::class)
        ->call('delete', $company->id)
        ->assertForbidden();
});
