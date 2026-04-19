<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->bothify('##A#'),
            'grade' => fake()->randomElement([10, 11, 12]),
            'block' => fake()->randomElement(['KHTN', 'KHXH', 'Cơ bản']),
            'shift' => fake()->randomElement(['morning', 'afternoon']),
            'homeroom_teacher_id' => null
        ];
    }
}
