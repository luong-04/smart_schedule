<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectConfiguration;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    // Hiển thị danh sách định mức đã tạo
    public function index()
    {
        // Lấy dữ liệu và nhóm theo trường 'grade'
        $groupedConfigs = SubjectConfiguration::with('subject')
                            ->get()
                            ->groupBy('grade');

        return view('admin.curriculum.index', compact('groupedConfigs'));
    }

    // Trang thêm mới định mức
    public function create()
    {
        $subjects = Subject::all();
        return view('admin.curriculum.create', compact('subjects'));
    }

    // Lưu định mức mới
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'grade' => 'required|integer|in:10,11,12',
            'slots_per_week' => 'required|integer|min:1|max:20',
        ], [
            'subject_id.required' => 'Vui lòng chọn môn học.',
            'slots_per_week.min' => 'Số tiết phải ít nhất là 1.'
        ]);

        // Kiểm tra xem đã tồn tại định mức cho môn này ở khối này chưa
        $exists = SubjectConfiguration::where('subject_id', $data['subject_id'])
                                     ->where('grade', $data['grade'])
                                     ->exists();
        if ($exists) {
            return back()->with('error', 'Môn học này đã được định mức cho khối này rồi!');
        }

        SubjectConfiguration::create($data);
        return redirect()->route('curriculum.index')->with('success', 'Đã thêm định mức thành công!');
    }

    // Trang chỉnh sửa
    public function edit(SubjectConfiguration $curriculum)
    {
        $subjects = Subject::all();
        return view('admin.curriculum.edit', compact('curriculum', 'subjects'));
    }

    // Cập nhật
    public function update(Request $request, SubjectConfiguration $curriculum)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'grade' => 'required|integer|in:10,11,12',
            'slots_per_week' => 'required|integer|min:1|max:20',
        ]);

        $curriculum->update($data);
        return redirect()->route('curriculum.index')->with('success', 'Đã cập nhật định mức!');
    }

    // Xóa định mức
    public function destroy(SubjectConfiguration $curriculum)
    {
        $curriculum->delete();
        return back()->with('success', 'Đã xóa định mức!');
    }
}