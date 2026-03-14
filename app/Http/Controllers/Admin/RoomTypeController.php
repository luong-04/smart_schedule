<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    /**
     * Hiển thị trang thêm mới loại phòng
     * Khắc phục lỗi: Call to undefined method create()
     */
    public function create()
    {
        return view('admin.room_types.create');
    }

    /**
     * Lưu loại phòng mới
     */
    public function store(Request $request) 
    {
        $data = $request->validate([
            'name' => 'required|string|unique:room_types,name'
        ], [
            'name.unique' => 'Tên loại phòng này đã tồn tại trong hệ thống.'
        ]);

        RoomType::create($data);

        // Chuyển hướng về trang danh sách tổng để xem kết quả
        return redirect()->route('rooms.index')->with('success', 'Đã thêm loại phòng mới thành công!');
    }

    /**
     * Hiển thị trang chỉnh sửa
     */
    public function edit(RoomType $roomType) 
    {
        return view('admin.room_types.edit', compact('roomType'));
    }

    /**
     * Cập nhật thông tin
     */
    public function update(Request $request, RoomType $roomType) 
    {
        $data = $request->validate([
            'name' => 'required|string|unique:room_types,name,' . $roomType->id
        ]);

        $roomType->update($data);

        // Sau khi sửa xong cũng quay về trang quản lý chung
        return redirect()->route('rooms.index')->with('success', 'Đã cập nhật thông tin loại phòng!');
    }

    /**
     * Xóa loại phòng
     */
    public function destroy(RoomType $roomType) 
    {
        // Kiểm tra ràng buộc dữ liệu: Không cho xóa nếu có phòng đang thuộc loại này
        if ($roomType->rooms()->count() > 0) {
            return back()->with('error', 'Không thể xóa! Đang có phòng học chi tiết thuộc loại hình này.');
        }

        $roomType->delete();
        
        return back()->with('success', 'Đã xóa loại phòng thành công!');
    }
}