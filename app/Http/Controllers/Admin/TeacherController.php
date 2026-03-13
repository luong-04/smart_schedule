<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::with('assignments.subject', 'assignments.classroom')->get();
        return view('admin.teachers.index', compact('teachers'));
    }
}
