<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    /**
     * Định nghĩa trạng thái mặc định của Model Lớp học (Classroom).
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Tên lớp học, ví dụ: 10A1
            'name' => fake()->unique()->bothify('##A#'),
            // Khối lớp (10, 11, hoặc 12)
            'grade' => fake()->randomElement([10, 11, 12]),
            // Ban học tập
            'block' => fake()->randomElement(['KHTN', 'KHXH', 'Cơ bản']),
            // Ca học
            'shift' => fake()->randomElement(['morning', 'afternoon']),
            'homeroom_teacher_id' => null
        ];
    }
}
