<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['call', 'email', 'meeting', 'task']),
            'subject' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_at' => fake()->optional()->dateTime(),
            'completed_at' => null,
            'user_id' => User::factory(),
            'activityable_type' => Contact::class,
            'activityable_id' => Contact::factory(),
        ];
    }
}
