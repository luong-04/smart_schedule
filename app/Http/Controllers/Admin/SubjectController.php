<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\RoomType;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    // 1. Trang danh sách
    /**
     * Danh sách tất cả các môn học kèm theo loại phòng tương ứng.
     * 
     * @return \Illuminate\View\View
     */
    public function index() {
        $subjects = Subject::with('roomType')->orderBy('name', 'asc')->get();
        return view('admin.subjects.index', compact('subjects'));
    }

    // 2. Trang thêm mới
    /**
     * Hiển thị form tạo môn học mới.
     * 
     * @return \Illuminate\View\View
     */
    public function create() {
        $roomTypes = RoomType::all();
        return view('admin.subjects.create', compact('roomTypes'));
    }

    // 3. Lưu dữ liệu
    /**
     * Lưu thông tin môn học mới vào cơ sở dữ liệu.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:theory,practice',
            'room_type_id' => 'nullable|exists:room_types,id'
        ]);

        if ($data['type'] == 'theory') {
            $data['room_type_id'] = null;
        }

        Subject::create($data);
        return redirect()->route('subjects.index')->with('success', 'Đã thêm môn học mới!');
    }

    // 4. Trang sửa dữ liệu
    /**
     * Hiển thị form chỉnh sửa thông tin môn học.
     * 
     * @param Subject $subject
     * @return \Illuminate\View\View
     */
    public function edit(Subject $subject) {
        $roomTypes = RoomType::all();
        return view('admin.subjects.edit', compact('subject', 'roomTypes'));
    }

    // 5. Cập nhật dữ liệu
    /**
     * Cập nhật thông tin môn học.
     * 
     * @param Request $request
     * @param Subject $subject
     * @return \Illuminate\Http\RedirectResponse
     */
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
        return redirect()->to(route('subjects.index') . '#subject-' . $subject->id)
            ->with('success', 'Đã cập nhật môn học thành công!');
    }

    // 6. Xóa dữ liệu
    /**
     * Xóa một môn học khỏi hệ thống.
     * 
     * @param Subject $subject
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Subject $subject) {
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Đã xóa môn học!');
    }

    // 7. TÍNH NĂNG MỚI: XÓA NHIỀU
    /**
     * Xóa hàng loạt môn học qua ID.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDelete(Request $request) {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            Subject::whereIn('id', $ids)->delete();
            return back()->with('success', 'Đã xóa thành công ' . count($ids) . ' môn học!');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 môn học để xóa!');
    }

    // 8. Import
    /**
     * Nhập dữ liệu môn học hàng loạt từ JSON.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
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
                        'room_type_id' => null
                    ]
                );
                $count++;
            }
        }
        return back()->with('success', "🎉 Đã import thành công $count Môn học!");
    }
}