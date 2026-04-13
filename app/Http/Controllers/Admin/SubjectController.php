<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\RoomType;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    // 1. Trang danh sách
    public function index() {
        // Load kèm thông tin loại phòng để hiển thị
        $subjects = Subject::with('roomType')->orderBy('name', 'asc')->get();
        return view('admin.subjects.index', compact('subjects'));
    }

    // 2. Trang thêm mới
    public function create() {
        $roomTypes = RoomType::all();
        return view('admin.subjects.create', compact('roomTypes'));
    }

    // 3. Lưu dữ liệu
    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:theory,practice',
            'room_type_id' => 'nullable|exists:room_types,id'
        ]);

        // Nếu là môn Lý thuyết thì tự động hủy loại phòng (cho chắc chắn)
        if ($data['type'] == 'theory') {
            $data['room_type_id'] = null;
        }

        Subject::create($data);
        return redirect()->route('subjects.index')->with('success', 'Đã thêm môn học mới!');
    }
    
    // 4. Trang chỉnh sửa
    public function edit(Subject $subject) {
        $roomTypes = RoomType::all();
        return view('admin.subjects.edit', compact('subject', 'roomTypes'));
    }

    // 5. Cập nhật dữ liệu
    public function update(Request $request, Subject $subject) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:theory,practice',
            'room_type_id' => 'nullable|exists:room_types,id'
        ]);

        if ($data['type'] == 'theory') {
            $data['room_type_id'] = null;
        }

        $subject->update($data);
        return redirect()->route('subjects.index')->with('success', 'Đã cập nhật môn học!');
    }

    // 6. Xóa dữ liệu
    public function destroy(Subject $subject) {
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Đã xóa môn học!');
    }
    public function import(Request $request) {
        $request->validate(['import_data' => 'required|string']);
        $subjects = json_decode($request->import_data, true);
        $count = 0;
        foreach ($subjects as $s) {
            if (!empty($s['name'])) {
                $type = (mb_strtolower($s['type'] ?? '', 'UTF-8') == 'thực hành' || strtolower($s['type'] ?? '') == 'practice') ? 'practice' : 'theory';
                Subject::updateOrCreate(
                    ['name' => $s['name']],
                    [
                        'type' => $type,
                        'room_type_id' => null // Import tự động đặt là phòng thường (Lý thuyết)
                    ]
                );
                $count++;
            }
        }
        return back()->with('success', "🎉 Đã import thành công $count Môn học!");
    }
}