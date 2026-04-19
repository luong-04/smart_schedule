<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Assignment;
use App\Models\Teacher;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * Định nghĩa trạng thái mặc định của Model Thời khóa biểu (Schedule).
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_name' => 'Học kỳ 1 - 2024-2025',
            'applies_from' => now()->startOfWeek(),
            'applies_to' => now()->startOfWeek()->addYears(1),
            'assignment_id' => Assignment::factory(),
            'teacher_id' => Teacher::factory(),
            'class_id' => Classroom::factory(),
            'room_id' => null,
            'day_of_week' => fake()->numberBetween(2, 7),
            'period' => fake()->numberBetween(1, 10),
        ];
    }
}
