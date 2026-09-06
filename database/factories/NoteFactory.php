<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'body' => fake()->paragraph(),
            'user_id' => User::factory(),
            'noteable_type' => Contact::class,
            'noteable_id' => Contact::factory(),
        ];
    }
}
