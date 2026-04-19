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

    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'class_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
        ];
    }
}
