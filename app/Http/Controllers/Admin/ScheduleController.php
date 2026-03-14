<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\Room;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * TRANG DASHBOARD: Hiển thị thống kê tổng quan
     */
    public function dashboard()
    {
        $stats = [
            'teachers'    => Teacher::count(),
            'classrooms'   => Classroom::count(),
            'rooms'       => Room::count(),
            'assignments' => Assignment::count()
        ];

        // Lấy các phân công mới nhất để hiển thị ở bảng "Hoạt động gần đây"
        $recentAssignments = Assignment::with(['teacher', 'subject', 'classroom'])
                            ->latest()
                            ->take(5)
                            ->get();

        return view('admin.dashboard', compact('stats', 'recentAssignments'));
    }

    /**
     * TRANG MA TRẬN TKB: Giao diện kéo thả
     */
    public function index(Request $request)
    {
        $classes = Classroom::all();
        // Mặc định chọn lớp đầu tiên nếu không có class_id truyền lên
        $selectedClassId = $request->get('class_id', $classes->first()?->id);
        
        // Lấy danh sách môn được phân công cho lớp này (Dùng làm kho dữ liệu kéo thả)
        $assignments = Assignment::with(['teacher', 'subject'])
                        ->where('class_id', $selectedClassId)->get();

        // Lấy dữ liệu TKB đã lưu
        $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
                        ->with('assignment.subject', 'assignment.teacher')
                        ->get();

        return view('admin.schedules.index', compact('classes', 'assignments', 'schedules', 'selectedClassId'));
    }

    /**
     * API LƯU TKB: Xử lý lưu qua AJAX
     */
    public function save(Request $request) 
    {
        $schedules = $request->input('schedules'); 
        
        foreach ($schedules as $item) {
            // Cập nhật hoặc tạo mới tiết học dựa trên thời gian và tên bản TKB
            Schedule::updateOrCreate(
                [
                    'day_of_week'   => $item['day_of_week'],
                    'period'        => $item['period'],
                    'schedule_name' => 'Học kỳ 1',
                ],
                ['assignment_id' => $item['assignment_id']]
            );
        }

        return response()->json(['status' => 'success']);
    }
}