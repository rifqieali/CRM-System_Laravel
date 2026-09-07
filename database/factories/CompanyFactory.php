<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake('id_ID')->company(),
            'industry' => fake()->randomElement(['tech', 'finance', 'healthcare', 'retail', 'manufacturing', 'other']),
            'size' => fake()->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
            'website' => fake()->domainName(),
            'phone' => fake('id_ID')->phoneNumber(),
            'address' => fake('id_ID')->streetAddress(),
            'city' => fake('id_ID')->city(),
            'country' => 'Indonesia',
            'owner_id' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
