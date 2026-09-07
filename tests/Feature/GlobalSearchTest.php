<?php

declare(strict_types=1);

use App\Livewire\GlobalSearch;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('renders search input in topnav for authenticated users', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cari contacts, companies, deals...');
});

it('does not crash when search input renders for guests', function (): void {
    $this->get(route('login'))
        ->assertOk();
});

it('searches contacts by first name, last name, and email', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    Contact::factory()->for($user, 'owner')->create(['first_name' => 'Budi', 'last_name' => 'Santoso', 'email' => 'budi@acme.test']);
    Contact::factory()->for($user, 'owner')->create(['first_name' => 'Ani', 'last_name' => 'Wijaya', 'email' => 'ani@other.test']);

    Livewire::actingAs($user)
        ->test(GlobalSearch::class)
        ->set('query', 'Budi')
        ->assertSet('open', true)
        ->assertSee('Budi Santoso')
        ->assertDontSee('Ani Wijaya');
});

it('searches contacts by email substring', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    Contact::factory()->for($user, 'owner')->create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'unique@match.test']);
    Contact::factory()->for($user, 'owner')->create(['first_name' => 'C', 'last_name' => 'D', 'email' => 'other@nope.test']);

    Livewire::actingAs($user)
        ->test(GlobalSearch::class)
        ->set('query', 'unique@match')
        ->assertSee('A B')
        ->assertDontSee('C D');
});

it('searches companies by name', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    Company::factory()->for($user, 'owner')->create(['name' => 'Acme Corp']);
    Company::factory()->for($user, 'owner')->create(['name' => 'Other Inc']);

    Livewire::actingAs($user)
        ->test(GlobalSearch::class)
        ->set('query', 'Acme')
        ->assertSee('Acme Corp')
        ->assertDontSee('Other Inc');
});

it('searches deals by name', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    $contact = Contact::factory()->for($user, 'owner')->create();
    Deal::factory()->for($user, 'owner')->for($contact, 'contact')->create(['name' => 'Q4 Renewal']);
    Deal::factory()->for($user, 'owner')->for($contact, 'contact')->create(['name' => 'Other Deal']);

    Livewire::actingAs($user)
        ->test(GlobalSearch::class)
        ->set('query', 'Q4')
        ->assertSee('Q4 Renewal')
        ->assertDontSee('Other Deal');
});

it('scopes search results to user', function (): void {
    $sales1 = User::factory()->create();
    $sales1->assignRole('Sales');
    $sales2 = User::factory()->create();
    $sales2->assignRole('Sales');

    Contact::factory()->for($sales1, 'owner')->create(['first_name' => 'MineFind', 'last_name' => 'S1']);
    Contact::factory()->for($sales2, 'owner')->create(['first_name' => 'OtherFind', 'last_name' => 'S2']);

    Livewire::actingAs($sales1)
        ->test(GlobalSearch::class)
        ->set('query', 'Find')
        ->assertSee('MineFind S1')
        ->assertDontSee('OtherFind S2');
});

it('shows empty state when no results', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');

    Livewire::actingAs($user)
        ->test(GlobalSearch::class)
        ->set('query', 'zzznevermatch')
        ->assertSet('open', true)
        ->assertSee('Tidak ada hasil untuk');
});

it('ignores queries shorter than 2 characters', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');
    Contact::factory()->for($user, 'owner')->create(['first_name' => 'A', 'last_name' => 'B']);

    Livewire::actingAs($user)
        ->test(GlobalSearch::class)
        ->set('query', 'A')
        ->assertSet('open', false)
        ->assertDontSee('A B');
});

it('clear button resets query and closes dropdown', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Sales');

    Livewire::actingAs($user)
        ->test(GlobalSearch::class)
        ->set('query', 'Acme')
        ->assertSet('open', true)
        ->call('clear')
        ->assertSet('query', '')
        ->assertSet('open', false);
});
