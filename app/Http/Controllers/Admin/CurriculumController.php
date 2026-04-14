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
            'block' => 'required|string|in:KHTN,KHXH,Cơ bản', 
            'slots_per_week' => 'required|integer|min:1|max:20',
        ], [
            'subject_id.required' => 'Vui lòng chọn môn học.',
            'slots_per_week.min' => 'Số tiết phải ít nhất là 1.'
        ]);

        // Kiểm tra xem đã tồn tại định mức cho môn này ở KHỐI và TỔ HỢP này chưa
        $exists = SubjectConfiguration::where('subject_id', $data['subject_id'])
                                     ->where('grade', $data['grade'])
                                     ->where('block', $data['block']) 
                                     ->exists();
        if ($exists) {
            return back()->with('error', 'Môn học này đã được định mức cho Khối và Tổ hợp này rồi!');
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
            'block' => 'required|string|in:KHTN,KHXH,Cơ bản', 
            'slots_per_week' => 'required|integer|min:1|max:20',
        ]);

        $curriculum->update($data);
        return redirect()->route('curriculum.index')->with('success', 'Đã cập nhật định mức!');
    }

    // Xóa 1 định mức
    public function destroy(SubjectConfiguration $curriculum)
    {
        $curriculum->delete();
        return back()->with('success', 'Đã xóa định mức!');
    }

    // TÍNH NĂNG MỚI: XÓA NHIỀU ĐỊNH MỨC CÙNG LÚC
    public function bulkDelete(Request $request) 
    {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            SubjectConfiguration::whereIn('id', $ids)->delete();
            return back()->with('success', 'Đã xóa thành công ' . count($ids) . ' định mức chương trình học!');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 định mức để xóa!');
    }
    //IMPORT ĐỊNH MỨC TỪ EXCEL
    public function import(Request $request) 
    {
        $request->validate(['import_data' => 'required|string']);
        $configs = json_decode($request->import_data, true);
        $count = 0;
        $errorSubjects = []; // Mảng lưu các môn bị sai tên
        
        foreach ($configs as $c) {
            if (!empty($c['subject_name']) && !empty($c['grade']) && !empty($c['block'])) {
                
                // Tìm môn học bằng LIKE để tránh lỗi dư khoảng trắng
                $subjectName = trim($c['subject_name']);
                $subject = Subject::where('name', 'LIKE', '%' . $subjectName . '%')->first();
                
                if ($subject) {
                    SubjectConfiguration::updateOrCreate(
                        [
                            'subject_id' => $subject->id,
                            'grade' => $c['grade'],
                            'block' => $c['block']
                        ],
                        [
                            'slots_per_week' => $c['slots'] ?? 2
                        ]
                    );
                    $count++;
                } else {
                    $errorSubjects[] = $subjectName; // Ghi nhận môn tìm không thấy
                }
            }
        }

        if (count($errorSubjects) > 0) {
            $errStr = implode(', ', array_unique($errorSubjects));
            return back()->with('error', "Import được $count dòng. NHƯNG bỏ qua các môn không có trong hệ thống: $errStr. Vui lòng sửa tên file Excel cho giống tên trong web!");
        }

        return back()->with('success', "🎉 Đã import thành công $count định mức chương trình học!");
    }
}