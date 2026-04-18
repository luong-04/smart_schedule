<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index() {
        $roomTypes = RoomType::all();
        $rooms = Room::with('roomType')->orderBy('name', 'asc')->get();
        return view('admin.rooms.index', compact('roomTypes', 'rooms'));
    }

    public function create() {
        $roomTypes = RoomType::all();
        return view('admin.rooms.create', compact('roomTypes'));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string',
            'room_type_id' => 'required|exists:room_types,id'
        ]);
        Room::create($data);
        return redirect()->route('rooms.index')->with('success', 'Đã thêm phòng mới!');
    }

    public function edit(Room $room) {
        $roomTypes = RoomType::all();
        return view('admin.rooms.edit', compact('room', 'roomTypes'));
    }

    public function update(Request $request, Room $room) {
        $data = $request->validate([
            'name' => 'required|string',
            'room_type_id' => 'required|exists:room_types,id'
        ]);
        $room->update($data);
        return redirect()->to(route('rooms.index') . '#room-' . $room->id)
            ->with('success', 'Đã cập nhật thông tin phòng học thành công!');
    }

    public function destroy(Room $room) {
        $room->delete();
        return redirect()->route('rooms.index')->with('success', 'Đã xóa phòng!');
    }

    // TÍNH NĂNG MỚI: XÓA NHIỀU PHÒNG HỌC
    public function bulkDelete(Request $request) {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            Room::whereIn('id', $ids)->delete();
            return back()->with('success', 'Đã xóa thành công ' . count($ids) . ' phòng học!');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 phòng học để xóa!');
    }
}