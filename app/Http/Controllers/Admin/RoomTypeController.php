<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function create()
    {
        return view('admin.room_types.create');
    }

    public function store(Request $request) 
    {
        $data = $request->validate([
            'name' => 'required|string|unique:room_types,name'
        ], [
            'name.unique' => 'Tên loại phòng này đã tồn tại trong hệ thống.'
        ]);

        RoomType::create($data);
        return redirect()->route('rooms.index')->with('success', 'Đã thêm loại phòng mới thành công!');
    }

    public function edit(RoomType $roomType) 
    {
        return view('admin.room_types.edit', compact('roomType'));
    }

    public function update(Request $request, RoomType $roomType) 
    {
        $data = $request->validate([
            'name' => 'required|string|unique:room_types,name,' . $roomType->id
        ]);

        $roomType->update($data);
        return redirect()->route('rooms.index')->with('success', 'Đã cập nhật thông tin loại phòng!');
    }

    public function destroy(RoomType $roomType) 
    {
        if ($roomType->rooms()->count() > 0) {
            return redirect()->route('rooms.index')->with('error', 'Không thể xóa loại phòng này vì đang có phòng học thuộc loại này!');
        }
        $roomType->delete();
        return redirect()->route('rooms.index')->with('success', 'Đã xóa loại phòng thành công!');
    }

    // TÍNH NĂNG MỚI: XÓA NHIỀU LOẠI PHÒNG
    public function bulkDelete(Request $request) {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            // Chỉ lấy những loại phòng KHÔNG có phòng học nào bên trong để tránh lỗi khóa ngoại
            $deletableIds = RoomType::whereIn('id', $ids)->doesntHave('rooms')->pluck('id');
            
            if ($deletableIds->isEmpty()) {
                return back()->with('error', 'Không thể xóa! Các loại phòng bạn chọn đều đang chứa phòng học.');
            }

            RoomType::whereIn('id', $deletableIds)->delete();
            $skipped = count($ids) - $deletableIds->count();
            
            $msg = 'Đã xóa ' . $deletableIds->count() . ' loại phòng.';
            if ($skipped > 0) $msg .= " Bỏ qua $skipped loại phòng do đang chứa phòng học.";
            
            return back()->with('success', $msg);
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 loại phòng để xóa!');
    }
}