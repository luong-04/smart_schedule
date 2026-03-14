<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    // 1. Trang danh sách (70% diện tích nội dung)
    public function index() {
        $subjects = Subject::orderBy('name', 'asc')->get();
        return view('admin.subjects.index', compact('subjects'));
    }

    // 2. Trang thêm mới (Trang riêng)
    public function create() {
        return view('admin.subjects.create');
    }

    // 3. Lưu dữ liệu
    public function store(Request $request) {
        $request->validate(['name' => 'required', 'type' => 'required']);
        Subject::create($request->all());
        return redirect()->route('subjects.index');
    }
    
    public function update(Request $request, Subject $subject) {
        $request->validate(['name' => 'required', 'type' => 'required']);
        $subject->update($request->all());
        return redirect()->route('subjects.index');
    }

    // 4. Trang chỉnh sửa (Trang riêng)
    public function edit(Subject $subject) {
        return view('admin.subjects.edit', compact('subject'));
    }

    // 6. Xóa dữ liệu
    public function destroy(Subject $subject) {
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Đã xóa môn học!');
    }
}