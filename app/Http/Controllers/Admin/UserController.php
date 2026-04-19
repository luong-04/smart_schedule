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
    /**
     * Hiển thị danh sách tất cả người dùng (nhân viên) trong hệ thống.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Lấy tất cả user trừ tài khoản đang đăng nhập (để Admin Tổng không tự xóa mình)
        $users = User::with('permissions')->where('id', '!=', auth()->id())->get();
        return view('admin.users.index', compact('users'));
    }

    // 2. Hiển thị Form tạo nhân viên mới
    /**
     * Hiển thị form tạo mới tài khoản người dùng và gán quyền.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Lấy danh sách tất cả các quyền trong database để in ra màn hình dạng Checkbox
        $permissions = Permission::all();
        return view('admin.users.create', compact('permissions'));
    }

    // 3. Xử lý lưu dữ liệu từ Form
    /**
     * Lưu thông tin người dùng mới và các quyền được gán.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
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
    /**
     * Xóa một tài khoản người dùng khỏi hệ thống.
     * 
     * @param int $id ID của người dùng.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Bạn không thể tự xóa chính mình!');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Đã xóa tài khoản!');
    }

    // 4b. Trang Sửa nhân viên (MỚI BỔ SUNG)
    /**
     * Hiển thị form chỉnh sửa thông tin người dùng và quyền hạn.
     * 
     * @param User $user
     * @return \Illuminate\View\View
     */
    public function edit(User $user)
    {
        $permissions = Permission::all();
        return view('admin.users.edit', compact('user', 'permissions'));
    }

    // 4c. Cập nhật nhân viên (MỚI BỔ SUNG)
    /**
     * Cập nhật thông tin người dùng và đồng bộ lại quyền hạn.
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8', // Để trống là không đổi mật khẩu
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Đồng bộ lại quyền
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        } else {
            $user->syncPermissions([]); // Xóa hết quyền nếu không tick ô nào
        }

        return redirect()->route('users.index')->with('success', 'Cập nhật tài khoản thành công!');
    }

    // 5. TÍNH NĂNG MỚI: XÓA NHIỀU
    /**
     * Xóa hàng loạt tài khoản người dùng qua ID.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            // Loại bỏ ID của chính mình nếu lỡ tick vào
            $ids = array_diff($ids, [auth()->id()]);
            User::whereIn('id', $ids)->delete();
            return back()->with('success', 'Đã xóa thành công ' . count($ids) . ' tài khoản!');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 tài khoản để xóa!');
    }
}