<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 1. Hiển thị danh sách nhân viên
    public function index()
    {
        // Lấy tất cả user trừ tài khoản đang đăng nhập (để Admin Tổng không tự xóa mình)
        $users = User::where('id', '!=', auth()->id())->get();
        return view('admin.users.index', compact('users'));
    }

    // 2. Hiển thị Form tạo nhân viên mới
    public function create()
    {
        // Lấy danh sách tất cả các quyền trong database để in ra màn hình dạng Checkbox
        $permissions = Permission::all();
        return view('admin.users.create', compact('permissions'));
    }

    // 3. Xử lý lưu dữ liệu từ Form
    public function store(Request $request)
    {
        // Kiểm tra dữ liệu hợp lệ
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // Tạo tài khoản mới vào bảng users
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Gán các quyền được tick chọn cho user này (nếu có chọn)
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        return redirect()->route('users.index')->with('success', 'Tạo tài khoản và phân quyền thành công!');
    }

    // 4. Xóa nhân viên
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Đã xóa tài khoản!');
    }
}