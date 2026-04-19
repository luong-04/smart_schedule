<?php

namespace Tests\Feature\Admin;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduleConstraintTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $scheduleName = 'Học kỳ 1 - 2024-2025';

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::create(['name' => 'Super Admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);

        // Seed settings để đảm bảo logic stable
        \App\Models\Setting::create(['key' => 'active_schedule', 'value' => $this->scheduleName]);
        \App\Models\Setting::create(['key' => 'check_teacher_conflict', 'value' => '1']);
        \App\Models\Setting::create(['key' => 'check_room_conflict', 'value' => '1']);
    }

    /** @test */
    public function it_can_save_a_valid_schedule_item()
    {
        $class = Classroom::factory()->create(['shift' => 'morning']);
        $teacher = Teacher::factory()->create();
        $assignment = Assignment::factory()->create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.schedules.save'), [
            'class_id' => $class->id,
            'applies_from' => '2024-09-01',
            'applies_to' => '2024-09-07',
            'schedules' => [
                [
                    'assignment_id' => $assignment->id,
                    'day_of_week' => 2,
                    'period' => 2, // Tiết 2 Thứ 2 (Tránh Chờ cờ)
                    'room_id' => null
                ]
            ]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('schedules', [
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 2,
            'period' => 2
        ]);
    }

    /** @test */
    public function it_prevents_teacher_double_booking_via_database_constraint()
    {
        // 1. Setup data
        $class1 = Classroom::factory()->create(['shift' => 'morning']);
        $class2 = Classroom::factory()->create(['shift' => 'morning']);
        $teacher = Teacher::factory()->create();
        
        $assignment1 = Assignment::factory()->create(['class_id' => $class1->id, 'teacher_id' => $teacher->id]);
        $assignment2 = Assignment::factory()->create(['class_id' => $class2->id, 'teacher_id' => $teacher->id]);

        $dateFrom = '2024-09-01';
        $dateTo = '2024-09-07';

        // 2. Tạo lịch cho lớp 1 trước (Sử dụng DB::table để khớp định dạng ngày với Controller trong SQLite)
        \Illuminate\Support\Facades\DB::table('schedules')->insert([
            'schedule_name' => $this->scheduleName,
            'applies_from'  => $dateFrom,
            'applies_to'    => $dateTo,
            'class_id'      => $class1->id,
            'teacher_id'    => $teacher->id,
            'assignment_id' => $assignment1->id,
            'day_of_week'   => 2,
            'period'        => 5,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);

        // 3. Fake request lưu lịch cho lớp 2 vào ĐÚNG thời điểm đó
        $response = $this->actingAs($this->admin)->postJson(route('admin.schedules.save'), [
            'class_id'     => $class2->id,
            'applies_from' => $dateFrom,
            'applies_to'   => $dateTo,
            'schedules'    => [
                [
                    'assignment_id' => $assignment2->id,
                    'day_of_week'   => 2,
                    'period'        => 5
                ]
            ]
        ]);

        // 4. Kiểm tra: Phải trả về lỗi 422
        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'error']);
    }

    /** @test */
    public function it_respects_teacher_off_days()
    {
        // Giả sử GV nghỉ Thứ 3 (Tuesday = 3)
        $teacher = Teacher::factory()->create(['off_days' => [3]]);
        $class = Classroom::factory()->create(['shift' => 'morning']);
        $assignment = Assignment::factory()->create(['class_id' => $class->id, 'teacher_id' => $teacher->id]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.schedules.save'), [
            'class_id' => $class->id,
            'applies_from' => '2024-09-01',
            'applies_to' => '2024-09-07',
            'schedules' => [
                [
                    'assignment_id' => $assignment->id,
                    'day_of_week' => 3, // Xếp vào ngày nghỉ
                    'period' => 1
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'error']);
    }
}
