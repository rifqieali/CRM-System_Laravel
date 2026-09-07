<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->contact = Contact::factory()->create(['owner_id' => $this->owner->id]);
    $this->company = Company::factory()->create(['owner_id' => $this->owner->id]);
});

it('sets closed_at when stage transitions to won', function (): void {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'company_id' => $this->company->id,
        'owner_id' => $this->owner->id,
        'stage' => Deal::STAGE_PROSPECTING,
        'closed_at' => null,
    ]);

    expect($deal->closed_at)->toBeNull();

    $deal->update(['stage' => Deal::STAGE_WON]);

    expect($deal->fresh()->closed_at)->not->toBeNull();
});

it('sets closed_at when stage transitions to lost', function (): void {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'company_id' => $this->company->id,
        'owner_id' => $this->owner->id,
        'stage' => Deal::STAGE_PROPOSAL,
        'closed_at' => null,
    ]);

    $deal->update(['stage' => Deal::STAGE_LOST]);

    expect($deal->fresh()->closed_at)->not->toBeNull();
});

it('clears closed_at when stage transitions back to open', function (): void {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'company_id' => $this->company->id,
        'owner_id' => $this->owner->id,
        'stage' => Deal::STAGE_WON,
        'closed_at' => now(),
    ]);

    expect($deal->fresh()->closed_at)->not->toBeNull();

    $deal->update(['stage' => Deal::STAGE_NEGOTIATION]);

    expect($deal->fresh()->closed_at)->toBeNull();
});

it('rejects invalid stage values', function (): void {
    Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'company_id' => $this->company->id,
        'owner_id' => $this->owner->id,
        'stage' => 'garbage',
    ]);
})->throws(InvalidArgumentException::class);

it('casts value to decimal with 2 places', function (): void {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'company_id' => $this->company->id,
        'owner_id' => $this->owner->id,
        'value' => 12345.678,
    ]);

    expect((string) $deal->fresh()->value)->toBe('12345.68');
});

it('reports closed stage correctly', function (): void {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'company_id' => $this->company->id,
        'owner_id' => $this->owner->id,
        'stage' => Deal::STAGE_WON,
    ]);

    expect($deal->isClosed())->toBeTrue();
});
