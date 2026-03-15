<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    // Dashboard thống kê thực tế
    public function dashboard()
    {
        $stats = [
            'teachers'    => Teacher::count(),
            'classrooms'  => Classroom::count(),
            'rooms'       => Room::count(),
            'assignments' => Assignment::count()
        ];
        $recentAssignments = Assignment::with(['teacher', 'subject', 'classroom'])->latest()->take(5)->get();
        return view('admin.dashboard', compact('stats', 'recentAssignments'));
    }

    // Ma trận kéo thả
    public function index(Request $request)
    {
        $classes = Classroom::all();
        $selectedClassId = $request->get('class_id', $classes->first()?->id);
        
        // 1. Lấy thông tin lớp học hiện tại để biết buổi học (shift)
        $classroom = Classroom::findOrFail($selectedClassId);
    
        // 2. Lấy danh sách phân công của lớp này
        $assignments = Assignment::with(['teacher', 'subject'])
            ->where('class_id', $selectedClassId)
            ->get();
    
        // 3. Lấy dữ liệu Thời khóa biểu đã lưu (Biến gây lỗi nếu thiếu)
        $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
            ->with(['assignment.subject', 'assignment.teacher'])
            ->get();
    
        // 4. Lấy các cài đặt (Chào cờ, Sinh hoạt...)
        $settings = \App\Models\Setting::pluck('value', 'key')->all();
    
        // 5. Quan trọng: Truyền tất cả biến vào compact()
        return view('admin.schedules.index', compact(
            'classes', 
            'assignments', 
            'schedules', 
            'selectedClassId', 
            'classroom', 
            'settings'
        ));
    }

    // Lưu TKB với logic kiểm tra chuyên sâu
    public function save(Request $request) 
    {
        $classId = $request->input('class_id');
        $schedules = $request->input('schedules'); 
        $classroom = Classroom::findOrFail($classId);
        $settings = \App\Models\Setting::pluck('value', 'key')->all();
    
        $shift = $classroom->shift; // 'morning' hoặc 'afternoon'
        $maxConsecutive = $settings['max_consecutive_slots'] ?? 3; // Lấy cài đặt giới hạn số tiết
    
        $teacherDayPeriods = []; // Mảng dùng để đếm số tiết liên tiếp
    
        foreach ($schedules as $item) {
            $assignment = Assignment::with('teacher')->findOrFail($item['assignment_id']);
            $teacherId = $assignment->teacher_id;
            $d = $item['day_of_week'];
            $p = $item['period'];
    
            // 1. KHÓA CỨNG: Kiểm tra đè lên tiết cố định (Chào cờ/Sinh hoạt)
            $flagDay = $settings[$shift.'_flag_day'] ?? 2;
            $flagPeriod = $settings[$shift.'_flag_period'] ?? ($shift == 'morning' ? 1 : 10);
            $meetDay = $settings[$shift.'_meeting_day'] ?? 7;
            $meetPeriod = $settings[$shift.'_meeting_period'] ?? ($shift == 'morning' ? 5 : 10);
    
            if (($d == $flagDay && $p == $flagPeriod) || ($d == $meetDay && $p == $meetPeriod)) {
                return response()->json(['status' => 'error', 'message' => "Hệ thống từ chối: Không được phép xếp môn vào vị trí cố định của Chào cờ hoặc Sinh hoạt lớp!"]);
            }
    
            // 2. Thu thập dữ liệu để kiểm tra số tiết liên tiếp
            $teacherDayPeriods[$teacherId][$d][] = $p;
    
            // 3. Kiểm tra trùng lịch giáo viên (Dạy lớp khác)
            $conflict = Schedule::where('day_of_week', $d)->where('period', $p)
                ->whereHas('assignment', function($q) use ($teacherId, $classId) {
                    $q->where('teacher_id', $teacherId)->where('class_id', '!=', $classId);
                })->with(['assignment.classroom', 'assignment.subject'])->first();
    
            if ($conflict) {
                return response()->json([
                    'status' => 'error', 
                    'message' => "Trùng lịch: GV {$assignment->teacher->name} đang dạy lớp {$conflict->assignment->classroom->name} vào Thứ {$d} - Tiết {$p}!"
                ]);
            }
    
            // 4. Kiểm tra ngày xin nghỉ của giáo viên
            $offDays = is_array($assignment->teacher->off_days) ? $assignment->teacher->off_days : json_decode($assignment->teacher->off_days ?? '[]', true);
            if (in_array($d, $offDays)) {
                return response()->json(['status' => 'error', 'message' => "Lịch nghỉ: Giáo viên {$assignment->teacher->name} đã xin nghỉ vào Thứ {$d}!"]);
            }
        }
    
        // 5. RÀNG BUỘC LUẬT: Kiểm tra số tiết dạy liên tiếp
        foreach ($teacherDayPeriods as $tId => $days) {
            foreach ($days as $day => $periods) {
                sort($periods); // Sắp xếp tiết học từ nhỏ tới lớn
                $consecutive = 1;
                $maxFound = 1;
                for ($i = 1; $i < count($periods); $i++) {
                    if ($periods[$i] == $periods[$i-1] + 1) {
                        $consecutive++;
                        $maxFound = max($maxFound, $consecutive);
                    } else {
                        $consecutive = 1;
                    }
                }
                if ($maxFound > $maxConsecutive) {
                    $teacher = Teacher::find($tId);
                    return response()->json([
                        'status' => 'error', 
                        'message' => "Vi phạm cấu hình: GV {$teacher->name} dạy liên tiếp {$maxFound} tiết vào Thứ {$day} (Giới hạn hệ thống là {$maxConsecutive} tiết)!"
                    ]);
                }
            }
        }
    
        // Xóa TKB cũ và lưu TKB mới
        Schedule::whereHas('assignment', function($q) use ($classId) {
            $q->where('class_id', $classId);
        })->delete();
    
        foreach ($schedules as $item) {
            Schedule::create($item + ['schedule_name' => 'Học kỳ 1']);
        }
    
        return response()->json(['status' => 'success']);
    }

    // Danh sách TKB đã xếp (Tích hợp xem xổ xuống)
    public function list()
    {
        $groupedClasses = Classroom::all()->groupBy('grade');
        
        // Lấy tất cả TKB của tất cả các lớp để hiển thị trực tiếp
        $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
            ->with(['assignment.subject', 'assignment.teacher'])
            ->get();
            
        // Lấy cài đặt (Tên trường, Năm học, Cố định...)
        $settings = \App\Models\Setting::pluck('value', 'key')->all();

        return view('admin.schedules.list', compact('groupedClasses', 'schedules', 'settings'));
    }

    // Xem chi tiết/In TKB
    public function show($class_id)
    {
        $classroom = Classroom::findOrFail($class_id);
        $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
            ->whereHas('assignment', function($q) use ($class_id) { 
                $q->where('class_id', $class_id); 
            })
            ->with(['assignment.subject', 'assignment.teacher'])
            ->get();
            
        // Thêm dòng này để lấy thông tin Tên trường, Hiệu trưởng...
        $settings = Setting::pluck('value', 'key')->all();

        return view('admin.schedules.show', compact('classroom', 'schedules', 'settings'));
    }
}