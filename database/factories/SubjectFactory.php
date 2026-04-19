<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'type' => fake()->randomElement(['theory', 'practice']),
            'room_type_id' => null
        ];
    }
}
