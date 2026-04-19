<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Teacher;
use App\Models\Classroom;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    /**
     * Định nghĩa trạng thái mặc định của Model Phân công giảng dạy (Assignment).
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // ID của giáo viên
            'teacher_id' => Teacher::factory(),
            // ID của lớp học
            'class_id' => Classroom::factory(),
            // ID của môn học
            'subject_id' => Subject::factory(),
        ];
    }
}
