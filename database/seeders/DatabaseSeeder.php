<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Chạy toàn bộ tiến trình seeder chính của hệ thống.
     * Bao gồm: Phân quyền, Tài khoản Admin, và Dữ liệu mẫu.
     */
    public function run(): void
    {
        // 1. Tạo sẵn các quyền (tương ứng với các chức năng chính)
        $permissions = [
            'quan_ly_giao_vien',
            'quan_ly_mon_hoc',
            'quan_ly_lop_hoc',
            'quan_ly_xep_lich',
            'quan_ly_giam_thi',
            'quan_ly_cai_dat',
            'quan_ly_co_so_vat_chat',
            'quan_ly_chuong_trinh_hoc'
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // 2. Tạo vai trò Admin Tổng (Super Admin)
        $superAdmin = Role::findOrCreate('Super Admin');

        // 3. Tạo tài khoản đăng nhập mặc định
        $admin = User::firstOrCreate(
            ['email' => 'admin@nguyenbinhkhiem.com'],
            [
                'name' => 'Admin Tổng',
                'password' => Hash::make('admin'),
            ]
        );

        // 4. Gán vai trò Super Admin cho tài khoản
        $admin->assignRole($superAdmin);

        // 5. Nạp dữ liệu mẫu để chạy thử nghiệm
        $this->call([
            SampleDataSeeder::class,
            SampleScheduleSeeder::class,
        ]);
    }
}