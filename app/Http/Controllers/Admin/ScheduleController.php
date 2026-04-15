<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SubjectConfiguration;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Helper: Lấy tên lịch động từ Settings (thay vì hardcode 'Học kỳ 1')
     */
    private function getScheduleName(): string
    {
        $semester = Setting::getVal('semester', 'Học kỳ 1');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        return $semester . ' - ' . $schoolYear;
    }

    public function dashboard()
    {
        $scheduleName = $this->getScheduleName();

        $stats = [
            'teachers'    => Teacher::count(),
            'classrooms'  => Classroom::count(),
            'rooms'       => Room::count(),
            'assignments' => Assignment::count()
        ];
        
        $recentSchedules = Schedule::where('schedule_name', $scheduleName)
            ->with('assignment.classroom')
            ->latest('updated_at')
            ->get()
            ->unique(function ($item) {
                return $item->assignment->class_id;
            })
            ->take(5);

        return view('admin.dashboard', compact('stats', 'recentSchedules'));
    }

    public function index(Request $request)
    {
        $scheduleName = $this->getScheduleName();

        // ---> ĐÃ SỬA: Sắp xếp ngay từ lúc lấy dữ liệu từ Database
        $classes = Classroom::orderBy('grade', 'asc')->orderBy('name', 'asc')->get();
        
        // ---> ĐÃ SỬA: Chặn lỗi 404 khi Database chưa có lớp học nào <---
        if ($classes->isEmpty()) {
            return redirect()->route('classrooms.index')
                             ->with('error', 'Hệ thống yêu cầu: Bạn phải tạo ít nhất 1 Lớp học trước khi vào tính năng Xếp lịch!');
        }

        $selectedClassId = $request->get('class_id', $classes->first()?->id);
        $classroom = Classroom::findOrFail($selectedClassId);
    
        $allAssignments = Assignment::with(['teacher', 'subject'])
            ->where('class_id', $selectedClassId)
            ->get();
    
        // ĐÃ SỬA: Chỉ lấy lịch của lớp hiện tại (thay vì toàn trường)
        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->whereHas('assignment', fn($q) => $q->where('class_id', $selectedClassId))
            ->with(['assignment.subject', 'assignment.teacher', 'room'])
            ->get();
    
        $settings = Setting::pluck('value', 'key')->all();
        $rooms = Room::all();

        $assignFlag = $settings['assign_gvcn_flag_salute'] ?? 0;
        $assignMeeting = $settings['assign_gvcn_class_meeting'] ?? 0;
        
        // ĐÃ SỬA TẠI ĐÂY: Thêm fallback 'Cơ bản' để chống lỗi sập trang nếu lớp cũ chưa có Tổ hợp
        $blockName = $classroom->block ?? 'Cơ bản'; 

        $curriculums = SubjectConfiguration::where('grade', $classroom->grade)
            ->where('block', $blockName) // Đã bắt logic Tổ hợp
            ->pluck('slots_per_week', 'subject_id')->all();

        $validAssignments = collect();

        $teacherBusySlots = [];
        $teacherOtherDays = [];
        $roomBusySlots = []; 
        
        // ĐÃ SỬA: Chỉ select cột cần thiết cho otherSchedules (giảm tải dữ liệu)
        $otherSchedules = Schedule::where('schedule_name', $scheduleName)
            ->whereHas('assignment', function($q) use ($selectedClassId) {
                $q->where('class_id', '!=', $selectedClassId);
            })
            ->with(['assignment:id,teacher_id,class_id'])
            ->get(['id', 'assignment_id', 'room_id', 'day_of_week', 'period']);

        foreach($otherSchedules as $sch) {
            $tId = $sch->assignment->teacher_id;
            $rId = $sch->room_id;

            $teacherBusySlots[$tId][] = $sch->day_of_week . '-' . $sch->period;
            
            if (!isset($teacherOtherDays[$tId])) $teacherOtherDays[$tId] = [];
            if (!in_array($sch->day_of_week, $teacherOtherDays[$tId])) {
                $teacherOtherDays[$tId][] = $sch->day_of_week;
            }

            if ($rId) {
                $roomBusySlots[$rId][] = $sch->day_of_week . '-' . $sch->period;
            }
        }

        // ĐÃ SỬA: Pre-load counts thay vì query N+1 trong vòng lặp
        // Đếm tổng tiết đã xếp cho mỗi giáo viên (toàn trường)
        $teacherUsedCounts = Schedule::where('schedule_name', $scheduleName)
            ->join('assignments', 'schedules.assignment_id', '=', 'assignments.id')
            ->selectRaw('assignments.teacher_id, COUNT(*) as total')
            ->groupBy('assignments.teacher_id')
            ->pluck('total', 'teacher_id')
            ->all();

        // Đếm số tiết đã xếp cho mỗi assignment (lớp hiện tại)
        $assignmentUsedCounts = Schedule::where('schedule_name', $scheduleName)
            ->whereIn('assignment_id', $allAssignments->pluck('id'))
            ->selectRaw('assignment_id, COUNT(*) as total')
            ->groupBy('assignment_id')
            ->pluck('total', 'assignment_id')
            ->all();

        // Đếm lớp GVCN cho mỗi giáo viên
        $gvcnCounts = [];
        $teacherNames = $allAssignments->pluck('teacher.name', 'teacher_id')->unique();
        foreach ($teacherNames as $tId => $tName) {
            $gvcnCounts[$tId] = Classroom::where('homeroom_teacher', $tName)->count();
        }

        foreach($allAssignments as $as) {
            if (!isset($curriculums[$as->subject_id])) continue;
            
            $maxSubjectSlots = $curriculums[$as->subject_id];

            // ĐÃ SỬA: Dùng pre-loaded counts thay vì query trong loop
            $teacherUsed = $teacherUsedCounts[$as->teacher_id] ?? 0;
            
            $gvcnClassCount = $gvcnCounts[$as->teacher_id] ?? 0;
            if ($gvcnClassCount > 0) {
                if ($assignFlag) $teacherUsed += $gvcnClassCount;
                if ($assignMeeting) $teacherUsed += $gvcnClassCount;
            }
            
            $as->teacher_remaining = max(0, $as->teacher->max_slots_week - $teacherUsed);

            $subjectUsed = $assignmentUsedCounts[$as->id] ?? 0;
            $as->remaining_subject_slots = max(0, $maxSubjectSlots - $subjectUsed);

            $as->actual_remaining = min($as->teacher_remaining, $as->remaining_subject_slots);

            $validAssignments->push($as);
        }
        
        $assignments = $validAssignments;
    
        return view('admin.schedules.index', compact(
            'classes', 'assignments', 'schedules', 'selectedClassId', 'classroom', 'settings', 'rooms',
            'teacherBusySlots', 'teacherOtherDays', 'roomBusySlots'
        ));
    }

    public function save(Request $request) 
    {
        // ĐÃ SỬA: Thêm Backend Validation
        $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'schedules' => 'required|array|max:60',
            'schedules.*.assignment_id' => 'required|integer',
            'schedules.*.day_of_week' => 'required|integer|between:2,7',
            'schedules.*.period' => 'required|integer|between:1,10',
        ]);

        $scheduleName = $this->getScheduleName();
        $classId = $request->input('class_id');
        $schedules = $request->input('schedules'); 
        $classroom = Classroom::findOrFail($classId);
        $settings = Setting::pluck('value', 'key')->all();

        // ĐÃ SỬA: Chống IDOR - validate tất cả assignment_id phải thuộc class_id
        $validAssignmentIds = Assignment::where('class_id', $classId)->pluck('id')->toArray();
        foreach ($schedules as $item) {
            if (!in_array($item['assignment_id'], $validAssignmentIds)) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Dữ liệu không hợp lệ: Phân công không thuộc lớp này!'
                ]);
            }
        }
    
        $shiftStr = strtolower($classroom->shift ?? 'morning');
        
        $maxConsecutive = $settings['max_consecutive_slots'] ?? 3; 
        $maxDaysPerWeek = $settings['max_days_per_week'] ?? 6;
        $checkTeacherConflict = $settings['check_teacher_conflict'] ?? 0;
        $checkRoomConflict = $settings['check_room_conflict'] ?? 0;
    
        $teacherDayPeriods = []; 
        $assignmentCounts = []; 
    
        // ĐÃ SỬA: Pre-load tất cả assignments cần thiết 1 lần
        $assignmentIds = collect($schedules)->pluck('assignment_id')->unique()->toArray();
        $allAssignments = Assignment::with(['teacher', 'subject'])
            ->whereIn('id', $assignmentIds)
            ->get()
            ->keyBy('id');

        foreach ($schedules as $item) {
            $assignment = $allAssignments[$item['assignment_id']] ?? null;
            if (!$assignment) {
                return response()->json(['status' => 'error', 'message' => 'Phân công không tồn tại!']);
            }

            $teacherId = $assignment->teacher_id;
            $d = $item['day_of_week'];
            $p = $item['period'];
            $roomId = $item['room_id'] ?? null; 
            
            $assignmentCounts[$assignment->id] = ($assignmentCounts[$assignment->id] ?? 0) + 1;
    
            $flagDay = $settings[$shiftStr.'_flag_day'] ?? 2;
            $flagPeriod = $settings[$shiftStr.'_flag_period'] ?? ($shiftStr == 'morning' ? 1 : 10);
            $meetDay = $settings[$shiftStr.'_meeting_day'] ?? 7;
            $meetPeriod = $settings[$shiftStr.'_meeting_period'] ?? ($shiftStr == 'morning' ? 5 : 10);
    
            if (($d == $flagDay && $p == $flagPeriod) || ($d == $meetDay && $p == $meetPeriod)) {
                return response()->json(['status' => 'error', 'message' => "Hệ thống từ chối: Không được phép xếp môn đè lên Chào cờ hoặc Sinh hoạt lớp!"]);
            }
    
            $teacherDayPeriods[$teacherId][$d][] = $p;
    
            if ($checkTeacherConflict) {
                $conflict = Schedule::where('schedule_name', $scheduleName)
                    ->where('day_of_week', $d)->where('period', $p)
                    ->whereHas('assignment', function($q) use ($teacherId, $classId) {
                        $q->where('teacher_id', $teacherId)->where('class_id', '!=', $classId);
                    })->with(['assignment.classroom'])->first();
        
                if ($conflict) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => "Trùng lịch: GV {$assignment->teacher->name} đang dạy lớp {$conflict->assignment->classroom->name} vào Thứ {$d} - Tiết {$p}!"
                    ]);
                }
            }

            if ($checkRoomConflict && $roomId) {
                $roomConflict = Schedule::where('schedule_name', $scheduleName)
                    ->where('day_of_week', $d)->where('period', $p)->where('room_id', $roomId)
                    ->whereHas('assignment', function($q) use ($classId) {
                        $q->where('class_id', '!=', $classId);
                    })->with(['room', 'assignment.classroom'])->first();

                if ($roomConflict) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => "Trùng phòng học: {$roomConflict->room->name} đang được lớp {$roomConflict->assignment->classroom->name} sử dụng vào Thứ {$d} - Tiết {$p}!"
                    ]);
                }
            }
    
            $offDays = is_array($assignment->teacher->off_days) ? $assignment->teacher->off_days : json_decode($assignment->teacher->off_days ?? '[]', true);
            if (in_array($d, $offDays)) {
                return response()->json(['status' => 'error', 'message' => "Lịch nghỉ: Giáo viên {$assignment->teacher->name} đã xin nghỉ vào Thứ {$d}!"]);
            }
        }
        
        // ĐÃ SỬA TẠI ĐÂY: Thêm fallback 'Cơ bản' để check chống hack
        $blockName = $classroom->block ?? 'Cơ bản';

        // ĐÃ SỬA: Pre-load configs thay vì query trong loop
        $configs = SubjectConfiguration::where('grade', $classroom->grade)
            ->where('block', $blockName)
            ->pluck('slots_per_week', 'subject_id')
            ->all();

        foreach ($assignmentCounts as $asId => $count) {
             $asmt = $allAssignments[$asId] ?? null;
             if (!$asmt) continue;
             
             $maxSlots = $configs[$asmt->subject_id] ?? null;
             if (!$maxSlots || $count > $maxSlots) {
                  return response()->json(['status' => 'error', 'message' => "Vượt định mức: Môn {$asmt->subject->name} chỉ được phép xếp tối đa {$maxSlots} tiết/tuần cho khối {$classroom->grade}!"]);
             }
        }
    
        foreach ($teacherDayPeriods as $tId => $days) {
            $daysThisClass = array_keys($days);
            $daysOtherClasses = Schedule::where('schedule_name', $scheduleName)
                ->whereHas('assignment', function($q) use ($tId, $classId) {
                    $q->where('teacher_id', $tId)->where('class_id', '!=', $classId);
                })->pluck('day_of_week')->toArray();
            
            $totalUniqueDays = count(array_unique(array_merge($daysThisClass, $daysOtherClasses)));
            if ($totalUniqueDays > $maxDaysPerWeek) {
                $teacher = $allAssignments->first(fn($a) => $a->teacher_id == $tId)?->teacher;
                return response()->json([
                    'status' => 'error', 
                    'message' => "Giới hạn hệ thống: GV {$teacher->name} vượt quá số ngày dạy tối đa trong tuần (Đang xếp: {$totalUniqueDays} ngày, Cho phép: {$maxDaysPerWeek} ngày)."
                ]);
            }

            foreach ($days as $day => $periods) {
                sort($periods); 
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
                    $teacher = $allAssignments->first(fn($a) => $a->teacher_id == $tId)?->teacher;
                    return response()->json([
                        'status' => 'error', 
                        'message' => "Vi phạm cấu hình: GV {$teacher->name} dạy liên tiếp {$maxFound} tiết vào Thứ {$day} (Giới hạn hệ thống là {$maxConsecutive} tiết)!"
                    ]);
                }
            }
        }
    
        // Xóa lịch cũ của lớp (chỉ trong cùng schedule_name)
        Schedule::where('schedule_name', $scheduleName)
            ->whereHas('assignment', function($q) use ($classId) {
                $q->where('class_id', $classId);
            })->delete();
    
        foreach ($schedules as $item) {
            Schedule::create($item + ['schedule_name' => $scheduleName]);
        }
    
        return response()->json(['status' => 'success']);
    }

    public function list()
    {
        $scheduleName = $this->getScheduleName();

        $classes = Classroom::orderBy('grade')->orderBy('name')->get();
        $groupedClasses = $classes->groupBy('grade');
        $teachers = Teacher::orderBy('name')->get();
        
        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->with(['assignment.subject', 'assignment.teacher', 'assignment.classroom', 'room'])
            ->get();
            
        $settings = Setting::pluck('value', 'key')->all();

        return view('admin.schedules.list', compact('groupedClasses', 'classes', 'teachers', 'schedules', 'settings'));
    }

    public function show($class_id)
    {
        $scheduleName = $this->getScheduleName();

        $classroom = Classroom::findOrFail($class_id);
        $settings = Setting::pluck('value', 'key')->all();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->whereHas('assignment', fn($q) => $q->where('class_id', $class_id))
            ->with(['assignment.subject', 'assignment.teacher', 'room'])
            ->get();

        return view('admin.schedules.show', compact('classroom', 'schedules', 'settings'));
    }

    public function printAll()
    {
        $scheduleName = $this->getScheduleName();

        $classes = Classroom::orderBy('grade')->orderBy('name')->get();
        $groupedClasses = $classes->groupBy('grade');
        $settings = Setting::pluck('value', 'key')->all();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->with(['assignment.subject', 'assignment.teacher', 'assignment.classroom', 'room'])
            ->get();

        return view('admin.schedules.list', compact('groupedClasses', 'classes', 'schedules', 'settings'));
    }
}