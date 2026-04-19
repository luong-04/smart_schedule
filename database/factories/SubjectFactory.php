<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * Định nghĩa trạng thái mặc định của Model Môn học (Subject).
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'type' => fake()->randomElement(['theory', 'practice']),
            'room_type_id' => null
        ];
    }
}
