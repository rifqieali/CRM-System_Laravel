<?php

declare(strict_types=1);

use App\Livewire\Activities\Index;
use App\Livewire\Shared\LogActivityModal;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();
});

it('redirects guests from activities index', function (): void {
    $this->get(route('activities.index'))->assertRedirect(route('login'));
});

it('renders activities index for authenticated user', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('activities.index'))
        ->assertOk();
});

it('shows admin all activities across entities', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);
    $deal = Deal::factory()->create(['owner_id' => $sales->id, 'contact_id' => $contact->id, 'company_id' => $company->id]);

    $contact->activities()->create(['type' => 'call', 'subject' => 'P1', 'user_id' => $sales->id]);
    $company->activities()->create(['type' => 'email', 'subject' => 'P2', 'user_id' => $sales->id]);
    $deal->activities()->create(['type' => 'meeting', 'subject' => 'P3', 'user_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertSee('P1')
        ->assertSee('P2')
        ->assertSee('P3');
});

it('scopes activities for sales user to own and team records', function (): void {
    $manager = User::where('email', 'manager@crm.test')->firstOrFail();
    $sales1 = User::where('email', 'sales1@crm.test')->firstOrFail();
    $sales2 = User::where('email', 'sales2@crm.test')->firstOrFail();
    $sales3 = User::where('email', 'sales3@crm.test')->firstOrFail();

    $c1 = Contact::factory()->create(['owner_id' => $sales1->id]);
    $c2 = Contact::factory()->create(['owner_id' => $sales2->id]);
    $c3 = Contact::factory()->create(['owner_id' => $sales3->id]);

    $c1->activities()->create(['type' => 'call', 'subject' => 'Mine', 'user_id' => $sales1->id]);
    $c2->activities()->create(['type' => 'call', 'subject' => 'Team', 'user_id' => $sales1->id]);
    $c3->activities()->create(['type' => 'call', 'subject' => 'Other', 'user_id' => $sales3->id]);

    Livewire::actingAs($sales1)
        ->test(Index::class)
        ->assertSee('Mine')
        ->assertSee('Team')
        ->assertDontSee('Other');
});

it('filters activities by type', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $contact->activities()->create(['type' => 'call', 'subject' => 'Call me', 'user_id' => $sales->id]);
    $contact->activities()->create(['type' => 'email', 'subject' => 'Mail me', 'user_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('type', 'call')
        ->assertSee('Call me')
        ->assertDontSee('Mail me');
});

it('filters activities by entity type', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $company = Company::factory()->create(['owner_id' => $sales->id]);
    $contact->activities()->create(['type' => 'call', 'subject' => 'ContactActivity', 'user_id' => $sales->id]);
    $company->activities()->create(['type' => 'call', 'subject' => 'CompanyActivity', 'user_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('entityType', Contact::class)
        ->assertSee('ContactActivity')
        ->assertDontSee('CompanyActivity');
});

it('filters overdue activities', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $contact->activities()->create(['type' => 'task', 'subject' => 'OverdueTask', 'user_id' => $sales->id, 'due_at' => now()->subDays(3)]);
    $contact->activities()->create(['type' => 'task', 'subject' => 'FutureTask', 'user_id' => $sales->id, 'due_at' => now()->addDays(3)]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('dueFilter', 'overdue')
        ->assertSee('OverdueTask')
        ->assertDontSee('FutureTask');
});

it('filters activities due within 7 days', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $contact->activities()->create(['type' => 'task', 'subject' => 'InDays', 'user_id' => $sales->id, 'due_at' => now()->addDays(3)]);
    $contact->activities()->create(['type' => 'task', 'subject' => 'BeyondDays', 'user_id' => $sales->id, 'due_at' => now()->addDays(30)]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('dueFilter', 'week')
        ->assertSee('InDays')
        ->assertDontSee('BeyondDays');
});

it('filters completed activities', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $contact->activities()->create(['type' => 'task', 'subject' => 'DoneTask', 'user_id' => $sales->id, 'completed_at' => now()]);
    $contact->activities()->create(['type' => 'task', 'subject' => 'OpenTask', 'user_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('dueFilter', 'completed')
        ->assertSee('DoneTask')
        ->assertDontSee('OpenTask');
});

it('filters activities by owner', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales1 = User::where('email', 'sales1@crm.test')->firstOrFail();
    $sales2 = User::where('email', 'sales2@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales1->id]);
    $contact->activities()->create(['type' => 'call', 'subject' => 'S1Activity', 'user_id' => $sales1->id]);
    $contact->activities()->create(['type' => 'call', 'subject' => 'S2Activity', 'user_id' => $sales2->id]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('ownerId', (string) $sales1->id)
        ->assertSee('S1Activity')
        ->assertDontSee('S2Activity');
});

it('searches activities by subject and description', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $contact->activities()->create(['type' => 'call', 'subject' => 'FollowUp', 'description' => 'Discuss pricing', 'user_id' => $sales->id]);
    $contact->activities()->create(['type' => 'call', 'subject' => 'Intro', 'description' => 'First contact', 'user_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('search', 'pricing')
        ->assertSee('FollowUp')
        ->assertDontSee('Intro');
});

it('deletes an activity when authorized', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $activity = $contact->activities()->create(['type' => 'call', 'subject' => 'DeleteMe', 'user_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('delete', $activity->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
});

it('forbids deletion of activity not owned by other sales team', function (): void {
    $sales1 = User::where('email', 'sales1@crm.test')->firstOrFail();
    $sales3 = User::where('email', 'sales3@crm.test')->firstOrFail();

    $sales3Other = User::factory()->create();
    $sales3Other->assignRole('Sales');
    $sales3Other->update(['manager_id' => $sales3->id]);

    $contact = Contact::factory()->create(['owner_id' => $sales1->id]);
    $activity = $contact->activities()->create(['type' => 'call', 'subject' => 'Private', 'user_id' => $sales1->id]);

    Livewire::actingAs($sales3Other)
        ->test(Index::class)
        ->call('delete', $activity->id);

    $this->assertDatabaseHas('activities', ['id' => $activity->id]);
});

it('marks an activity as complete when authorized', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);
    $activity = $contact->activities()->create(['type' => 'task', 'subject' => 'MarkMe', 'user_id' => $sales->id, 'due_at' => now()->addDay()]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('markComplete', $activity->id)
        ->assertHasNoErrors();

    $activity->refresh();
    expect($activity->completed_at)->not->toBeNull();
});

it('logs activity from a contact via the shared modal', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(LogActivityModal::class, ['morphType' => Contact::class, 'morphId' => $contact->id])
        ->call('openModal')
        ->set('type', 'call')
        ->set('subject', 'Discuss renewal')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'activityable_type' => Contact::class,
        'activityable_id' => $contact->id,
        'subject' => 'Discuss renewal',
        'type' => 'call',
    ]);
});

it('rejects log activity for invalid type', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();
    $sales = User::where('email', 'sales1@crm.test')->firstOrFail();
    $contact = Contact::factory()->create(['owner_id' => $sales->id]);

    Livewire::actingAs($admin)
        ->test(LogActivityModal::class, ['morphType' => Contact::class, 'morphId' => $contact->id])
        ->call('openModal')
        ->set('type', 'invalid-type')
        ->set('subject', 'Test')
        ->call('save')
        ->assertHasErrors(['type']);
});

it('rejects log activity for disallowed morph type', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    expect(fn () => Activity::create([
        'type' => 'call',
        'subject' => 'X',
        'user_id' => $admin->id,
        'activityable_type' => User::class,
        'activityable_id' => $admin->id,
    ]))->toThrow(InvalidArgumentException::class);
});

it('resets filters via resetFilters', function (): void {
    $admin = User::where('email', 'admin@crm.test')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('search', 'something')
        ->set('type', 'call')
        ->set('entityType', Contact::class)
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('type', '')
        ->assertSet('entityType', '');
});
