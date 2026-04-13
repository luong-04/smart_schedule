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
    public function dashboard()
    {
        $stats = [
            'teachers'    => Teacher::count(),
            'classrooms'  => Classroom::count(),
            'rooms'       => Room::count(),
            'assignments' => Assignment::count()
        ];
        
        $recentSchedules = Schedule::with('assignment.classroom')
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
        $classes = Classroom::all();
        
        // ---> ĐÃ SỬA: Chặn lỗi 404 khi Database chưa có lớp học nào <---
        if ($classes->isEmpty()) {
            return redirect()->route('classrooms.index')
                             ->with('error', 'Hệ thống yêu cầu: Bạn phải tạo ít nhất 1 Lớp học trước khi vào tính năng Xếp lịch!');
        }
        // ------------------------------------------------------------------

        $selectedClassId = $request->get('class_id', $classes->first()?->id);
        $classroom = Classroom::findOrFail($selectedClassId);
    
        $allAssignments = Assignment::with(['teacher', 'subject'])
            ->where('class_id', $selectedClassId)
            ->get();
    
        $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
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
        
        $otherSchedules = Schedule::where('schedule_name', 'Học kỳ 1')
            ->whereHas('assignment', function($q) use ($selectedClassId) {
                $q->where('class_id', '!=', $selectedClassId);
            })->get();

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

        foreach($allAssignments as $as) {
            if (!isset($curriculums[$as->subject_id])) continue;
            
            $maxSubjectSlots = $curriculums[$as->subject_id];

            $teacherUsed = Schedule::whereHas('assignment', function($q) use ($as) {
                $q->where('teacher_id', $as->teacher_id);
            })->count();
            
            $gvcnClassCount = Classroom::where('homeroom_teacher', $as->teacher->name)->count();
            if ($gvcnClassCount > 0) {
                if ($assignFlag) $teacherUsed += $gvcnClassCount;
                if ($assignMeeting) $teacherUsed += $gvcnClassCount;
            }
            
            $as->teacher_remaining = max(0, $as->teacher->max_slots_week - $teacherUsed);

            $subjectUsed = Schedule::where('assignment_id', $as->id)->count();
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
        $classId = $request->input('class_id');
        $schedules = $request->input('schedules'); 
        $classroom = Classroom::findOrFail($classId);
        $settings = Setting::pluck('value', 'key')->all();
    
        $shiftStr = strtolower($classroom->shift ?? 'morning');
        
        $maxConsecutive = $settings['max_consecutive_slots'] ?? 3; 
        $maxDaysPerWeek = $settings['max_days_per_week'] ?? 6;
        $checkTeacherConflict = $settings['check_teacher_conflict'] ?? 0;
        $checkRoomConflict = $settings['check_room_conflict'] ?? 0;
    
        $teacherDayPeriods = []; 
        $assignmentCounts = []; 
    
        foreach ($schedules as $item) {
            $assignment = Assignment::with('teacher', 'subject')->findOrFail($item['assignment_id']);
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
                $conflict = Schedule::where('day_of_week', $d)->where('period', $p)
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
                $roomConflict = Schedule::where('day_of_week', $d)->where('period', $p)->where('room_id', $roomId)
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

        foreach ($assignmentCounts as $asId => $count) {
             $asmt = Assignment::with('subject')->find($asId);
             $config = SubjectConfiguration::where('subject_id', $asmt->subject_id)
                           ->where('grade', $classroom->grade)
                           ->where('block', $blockName) // Đã bắt logic Tổ hợp
                           ->first();
             if (!$config || $count > $config->slots_per_week) {
                  return response()->json(['status' => 'error', 'message' => "Vượt định mức: Môn {$asmt->subject->name} chỉ được phép xếp tối đa {$config->slots_per_week} tiết/tuần cho khối {$classroom->grade}!"]);
             }
        }
    
        foreach ($teacherDayPeriods as $tId => $days) {
            $daysThisClass = array_keys($days);
            $daysOtherClasses = Schedule::whereHas('assignment', function($q) use ($tId, $classId) {
                $q->where('teacher_id', $tId)->where('class_id', '!=', $classId);
            })->pluck('day_of_week')->toArray();
            
            $totalUniqueDays = count(array_unique(array_merge($daysThisClass, $daysOtherClasses)));
            if ($totalUniqueDays > $maxDaysPerWeek) {
                $teacher = Teacher::find($tId);
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
                    $teacher = Teacher::find($tId);
                    return response()->json([
                        'status' => 'error', 
                        'message' => "Vi phạm cấu hình: GV {$teacher->name} dạy liên tiếp {$maxFound} tiết vào Thứ {$day} (Giới hạn hệ thống là {$maxConsecutive} tiết)!"
                    ]);
                }
            }
        }
    
        Schedule::whereHas('assignment', function($q) use ($classId) {
            $q->where('class_id', $classId);
        })->delete();
    
        foreach ($schedules as $item) {
            Schedule::create($item + ['schedule_name' => 'Học kỳ 1']);
        }
    
        return response()->json(['status' => 'success']);
    }

    public function list()
    {
        $classes = Classroom::orderBy('grade')->orderBy('name')->get();
        $groupedClasses = $classes->groupBy('grade');
        $teachers = Teacher::orderBy('name')->get();
        
        $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
            ->with(['assignment.subject', 'assignment.teacher', 'assignment.classroom', 'room'])
            ->get();
            
        $settings = Setting::pluck('value', 'key')->all();

        return view('admin.schedules.list', compact('groupedClasses', 'classes', 'teachers', 'schedules', 'settings'));
    }
}