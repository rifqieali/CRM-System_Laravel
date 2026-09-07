<?php

declare(strict_types=1);

use App\Models\Tag;

it('generates a slug from name on create', function (): void {
    $tag = Tag::factory()->create(['name' => 'VIP Customer', 'slug' => null]);

    expect($tag->slug)->toBe('vip-customer');
});

it('keeps the explicit slug when provided', function (): void {
    $tag = Tag::factory()->create(['name' => 'VIP', 'slug' => 'custom-slug']);

    expect($tag->slug)->toBe('custom-slug');
});

it('appends incrementing suffix when slug collides', function (): void {
    Tag::factory()->create(['name' => 'Hot Lead', 'slug' => 'hot-lead']);

    $second = Tag::factory()->create(['name' => 'Hot Lead', 'slug' => null]);

    expect($second->slug)->toBe('hot-lead-2');

    $third = Tag::factory()->create(['name' => 'Hot Lead', 'slug' => null]);

    expect($third->slug)->toBe('hot-lead-3');
});
