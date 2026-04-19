<?php

namespace Tests\Feature\Admin;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup permissions and admin user
        $role = Role::create(['name' => 'Super Admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
    }

    /** @test */
    public function an_admin_can_view_the_teacher_index_page()
    {
        $response = $this->actingAs($this->admin)->get(route('teachers.index'));

        $response->assertStatus(200);
        $response->assertSee('Danh mục Giáo viên');
    }

    /** @test */
    public function an_admin_can_create_a_teacher()
    {
        $teacherData = [
            'name' => 'Test Teacher',
            'code' => 'GVTEST01',
            'department' => 'Tổ Toán',
            'max_slots_week' => 20,
            'off_days' => [2, 3]
        ];

        $response = $this->actingAs($this->admin)->post(route('teachers.store'), $teacherData);

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseHas('teachers', ['code' => 'GVTEST01']);
    }

    /** @test */
    public function an_admin_can_update_a_teacher()
    {
        $teacher = Teacher::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)->put(route('teachers.update', $teacher->id), [
            'name' => 'New Name',
            'code' => $teacher->code,
            'department' => $teacher->department,
            'max_slots_week' => 22
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'name' => 'New Name']);
    }

    /** @test */
    public function an_admin_can_delete_a_teacher()
    {
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('teachers.destroy', $teacher->id));

        $response->assertRedirect();
        // Check soft delete
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
    }
}
