<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo sẵn các quyền (tương ứng với các menu của bạn)
        $permissions = [
            'quan_ly_giao_vien',
            'quan_ly_mon_hoc',
            'quan_ly_lop_hoc',
            'quan_ly_xep_lich',
            'quan_ly_giam_thi',
            'quan_ly_cai_dat'
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // 2. Tạo chức vụ Admin Tổng
        $superAdmin = Role::findOrCreate('Super Admin');

        // 3. Tạo tài khoản đăng nhập cho bạn
        $admin = User::firstOrCreate(
            ['email' => 'admin@nguyenbinhkhiem.com'],
            [
                'name' => 'Admin Tổng',
                'password' => Hash::make('admin'),
            ]
        );

        // 4. Gán chức vụ Admin Tổng cho bạn
        $admin->assignRole($superAdmin);

        // 5. Thêm dữ liệu mẫu
        $this->call([
            SampleDataSeeder::class,
            SampleScheduleSeeder::class,
        ]);
    }
}