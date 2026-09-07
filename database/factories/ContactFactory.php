<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'first_name' => fake('id_ID')->firstName(),
            'last_name' => fake('id_ID')->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake('id_ID')->phoneNumber(),
            'mobile' => fake('id_ID')->phoneNumber(),
            'job_title' => fake()->jobTitle(),
            'company_id' => Company::factory(),
            'owner_id' => User::factory(),
            'source' => fake()->randomElement(['website', 'referral', 'cold-call', 'event', 'other']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
