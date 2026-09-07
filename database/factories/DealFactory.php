<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'value' => fake()->randomFloat(2, 1000000, 500000000),
            'currency' => 'IDR',
            'stage' => fake()->randomElement(['prospecting', 'qualification', 'proposal', 'negotiation', 'won', 'lost']),
            'probability' => fake()->optional()->numberBetween(0, 100),
            'expected_close_date' => fake()->optional()->date(),
            'closed_at' => null,
            'contact_id' => Contact::factory(),
            'company_id' => Company::factory(),
            'owner_id' => User::factory(),
        ];
    }
}
