<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Tag;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('allows activity on Contact', function (): void {
    $contact = Contact::factory()->create();

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'activityable_type' => Contact::class,
        'activityable_id' => $contact->id,
    ]);

    expect($activity->activityable->id)->toBe($contact->id);
});

it('allows activity on Company', function (): void {
    $company = Company::factory()->create();

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'activityable_type' => Company::class,
        'activityable_id' => $company->id,
    ]);

    expect($activity->activityable->id)->toBe($company->id);
});

it('allows activity on Deal', function (): void {
    $contact = Contact::factory()->create();
    $company = Company::factory()->create();
    $deal = Deal::factory()->create([
        'contact_id' => $contact->id,
        'company_id' => $company->id,
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'activityable_type' => Deal::class,
        'activityable_id' => $deal->id,
    ]);

    expect($activity->activityable->id)->toBe($deal->id);
});

it('rejects activity on disallowed morph type', function (): void {
    Activity::factory()->create([
        'user_id' => $this->user->id,
        'activityable_type' => User::class,
        'activityable_id' => $this->user->id,
    ]);
})->throws(InvalidArgumentException::class);

it('rejects activity on tag morph type', function (): void {
    $tag = Tag::factory()->create();

    Activity::factory()->create([
        'user_id' => $this->user->id,
        'activityable_type' => Tag::class,
        'activityable_id' => $tag->id,
    ]);
})->throws(InvalidArgumentException::class);
