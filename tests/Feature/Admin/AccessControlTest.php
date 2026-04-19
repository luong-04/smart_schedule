<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guests_cannot_access_admin_dashboard()
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect('/login');
    }

    /** @test */
    public function super_admin_can_access_everything()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function restricted_user_cannot_access_user_management()
    {
        // Tạo người dùng chỉ có quyền quản lý môn học
        Permission::create(['name' => 'quan_ly_mon_hoc']);
        $user = User::factory()->create();
        $user->givePermissionTo('quan_ly_mon_hoc');

        // Thử truy cập trang quản lý nhân viên (chỉ dành cho Super Admin)
        $response = $this->actingAs($user)->get(route('users.index'));
        
        // Trừ phi được cấp Role Super Admin, nếu không sẽ bị 403 Forbidden hoặc bị redirect tùy Middleware
        $response->assertStatus(403);
    }

    /** @test */
    public function restricted_user_can_access_allowed_modules()
    {
        Permission::create(['name' => 'quan_ly_mon_hoc']);
        $user = User::factory()->create();
        $user->givePermissionTo('quan_ly_mon_hoc');

        $response = $this->actingAs($user)->get(route('subjects.index'));
        $response->assertStatus(200);
    }
}
