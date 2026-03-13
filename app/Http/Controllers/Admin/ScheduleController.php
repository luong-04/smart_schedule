<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $classes = Classroom::all();
        $selectedClassId = $request->get('class_id', $classes->first()?->id);
        
        // Lấy danh sách phân công của lớp này để làm "kho dữ liệu" kéo thả
        $assignments = Assignment::with(['teacher', 'subject'])
                        ->where('class_id', $selectedClassId)->get();

        // Lấy TKB đã lưu trong database (nếu có)
        $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
                        ->with('assignment.subject', 'assignment.teacher')
                        ->get();

        return view('admin.schedules.index', compact('classes', 'assignments', 'schedules', 'selectedClassId'));
    }
}
