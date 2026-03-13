<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        $teachers = Teacher::all();
        $subjects = Subject::all();
        $classes = Classroom::all();
        $assignments = Assignment::with(['teacher', 'subject', 'classroom'])->get();
        
        return view('admin.assignments.index', compact('teachers', 'subjects', 'classes', 'assignments'));
    }

    public function store(Request $request)
    {
        Assignment::create($request->all());
        return redirect()->back()->with('success', 'Phân công thành công!');
    }
}
