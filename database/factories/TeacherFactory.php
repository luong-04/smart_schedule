<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'code' => 'GV' . fake()->unique()->numberBetween(100, 999),
            'department' => 'Tổ ' . fake()->randomElement(['Toán', 'Văn', 'Anh', 'Lý', 'Hóa']),
            'max_slots_week' => 24,
            'off_days' => []
        ];
    }
}
