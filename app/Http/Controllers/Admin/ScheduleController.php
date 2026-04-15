<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SubjectConfiguration;
use App\Services\ScheduleValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleValidationService $validator
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lấy tên lịch động từ Settings (có Cache).
     * Setting::getVal() đã được cache 6 tiếng qua SettingObserver.
     */
    private function getScheduleName(): string
    {
        $semester   = Setting::getVal('semester', 'Học kỳ 1');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        return $semester . ' - ' . $schoolYear;
    }

    /**
     * Tính toán biến ca học tập trung (DRY — tránh lặp lại trong View).
     * Trả về mảng gồm: shiftStr, flagDay, flagPeriod, meetDay, meetPeriod.
     */
    private function getShiftVars(Classroom $classroom, array $settings): array
    {
        $shiftStr   = strtolower($classroom->shift ?? 'morning');
        $flagDay    = $settings[$shiftStr . '_flag_day']    ?? 2;
        $flagPeriod = $settings[$shiftStr . '_flag_period'] ?? ($shiftStr === 'morning' ? 1 : 10);
        $meetDay    = $settings[$shiftStr . '_meeting_day']    ?? 7;
        $meetPeriod = $settings[$shiftStr . '_meeting_period'] ?? ($shiftStr === 'morning' ? 5 : 10);

        return compact('shiftStr', 'flagDay', 'flagPeriod', 'meetDay', 'meetPeriod');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTIONS
    // ─────────────────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $scheduleName = $this->getScheduleName();

        $stats = [
            'teachers'    => Teacher::count(),
            'classrooms'  => Classroom::count(),
            'rooms'       => Room::count(),
            'assignments' => Assignment::count(),
        ];

        // Eager load đầy đủ các relationship mà view dashboard cần
        $recentSchedules = Schedule::where('schedule_name', $scheduleName)
            ->with(['assignment.classroom', 'assignment.teacher', 'assignment.subject'])
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

        $classes = Classroom::orderBy('grade', 'asc')->orderBy('name', 'asc')->get();

        if ($classes->isEmpty()) {
            return redirect()->route('classrooms.index')
                ->with('error', 'Hệ thống yêu cầu: Bạn phải tạo ít nhất 1 Lớp học trước khi vào tính năng Xếp lịch!');
        }

        $selectedClassId = $request->get('class_id', $classes->first()?->id);
        $classroom       = Classroom::findOrFail($selectedClassId);

        $allAssignments = Assignment::with(['teacher', 'subject'])
            ->where('class_id', $selectedClassId)
            ->get();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->whereHas('assignment', fn($q) => $q->where('class_id', $selectedClassId))
            ->with(['assignment.subject', 'assignment.teacher', 'room'])
            ->get();

        $settings = Setting::pluck('value', 'key')->all();
        $rooms    = Room::all();

        $assignFlag    = $settings['assign_gvcn_flag_salute']    ?? 0;
        $assignMeeting = $settings['assign_gvcn_class_meeting']  ?? 0;

        // ── DRY: Tính toán shiftVars 1 lần ở Controller, truyền xuống View ──
        $shiftVars  = $this->getShiftVars($classroom, $settings);

        // Dùng Classroom::BLOCK_CO_BAN constant thay hardcode string
        $blockName  = $classroom->block ?? Classroom::BLOCK_CO_BAN;

        $curriculums = SubjectConfiguration::where('grade', $classroom->grade)
            ->where('block', $blockName)
            ->pluck('slots_per_week', 'subject_id')
            ->all();

        // Pre-load các lớp khác của giáo viên để kiểm tra conflict
        $otherSchedules = Schedule::where('schedule_name', $scheduleName)
            ->whereHas('assignment', function ($q) use ($selectedClassId) {
                $q->where('class_id', '!=', $selectedClassId);
            })
            ->with(['assignment:id,teacher_id,class_id'])
            ->get(['id', 'assignment_id', 'room_id', 'day_of_week', 'period']);

        $teacherBusySlots = [];
        $teacherOtherDays = [];
        $roomBusySlots    = [];

        foreach ($otherSchedules as $sch) {
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

        // Pre-load counts để tránh N+1
        $teacherUsedCounts = Schedule::where('schedule_name', $scheduleName)
            ->join('assignments', 'schedules.assignment_id', '=', 'assignments.id')
            ->selectRaw('assignments.teacher_id, COUNT(*) as total')
            ->groupBy('assignments.teacher_id')
            ->pluck('total', 'teacher_id')
            ->all();

        $assignmentUsedCounts = Schedule::where('schedule_name', $scheduleName)
            ->whereIn('assignment_id', $allAssignments->pluck('id'))
            ->selectRaw('assignment_id, COUNT(*) as total')
            ->groupBy('assignment_id')
            ->pluck('total', 'assignment_id')
            ->all();

        // Đếm lớp GVCN (dùng quan hệ qua ID thay vì query tên — nhưng vì DB lưu tên,
        // ta vẫn phải query theo tên. Đã gom vào 1 query thay vì N queries)
        $teacherNames = $allAssignments->pluck('teacher.name', 'teacher_id')->unique();
        $gvcnCounts   = [];
        if ($teacherNames->isNotEmpty()) {
            $homeroomCounts = Classroom::whereIn('homeroom_teacher', $teacherNames->values())
                ->selectRaw('homeroom_teacher, COUNT(*) as cnt')
                ->groupBy('homeroom_teacher')
                ->pluck('cnt', 'homeroom_teacher')
                ->all();

            foreach ($teacherNames as $tId => $tName) {
                $gvcnCounts[$tId] = $homeroomCounts[$tName] ?? 0;
            }
        }

        $validAssignments = collect();
        foreach ($allAssignments as $as) {
            if (!isset($curriculums[$as->subject_id])) continue;

            $maxSubjectSlots = $curriculums[$as->subject_id];
            $teacherUsed     = $teacherUsedCounts[$as->teacher_id] ?? 0;
            $gvcnClassCount  = $gvcnCounts[$as->teacher_id] ?? 0;

            if ($gvcnClassCount > 0) {
                if ($assignFlag)   $teacherUsed += $gvcnClassCount;
                if ($assignMeeting) $teacherUsed += $gvcnClassCount;
            }

            $as->teacher_remaining       = max(0, $as->teacher->max_slots_week - $teacherUsed);
            $subjectUsed                 = $assignmentUsedCounts[$as->id] ?? 0;
            $as->remaining_subject_slots = max(0, $maxSubjectSlots - $subjectUsed);
            $as->actual_remaining        = min($as->teacher_remaining, $as->remaining_subject_slots);

            $validAssignments->push($as);
        }

        $assignments = $validAssignments;

        return view('admin.schedules.index', compact(
            'classes', 'assignments', 'schedules', 'selectedClassId', 'classroom',
            'settings', 'rooms', 'teacherBusySlots', 'teacherOtherDays', 'roomBusySlots',
            'shiftVars'
        ));
    }

    /**
     * Lưu ma trận thời khóa biểu.
     * Dùng StoreScheduleRequest (Form Request) + ScheduleValidationService + DB::transaction.
     */
    public function save(StoreScheduleRequest $request)
    {
        $scheduleName = $this->getScheduleName();
        $classId      = $request->input('class_id');
        $schedules    = $request->input('schedules');
        $classroom    = Classroom::findOrFail($classId);
        $settings     = Setting::pluck('value', 'key')->all();

        // Chống IDOR — tất cả assignment_id phải thuộc class_id
        $validAssignmentIds = Assignment::where('class_id', $classId)->pluck('id')->toArray();
        foreach ($schedules as $item) {
            if (!in_array($item['assignment_id'], $validAssignmentIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Dữ liệu không hợp lệ: Phân công không thuộc lớp này!',
                ]);
            }
        }

        // Pre-load tất cả assignments 1 lần
        $assignmentIds  = collect($schedules)->pluck('assignment_id')->unique()->toArray();
        $allAssignments = Assignment::with(['teacher', 'subject'])
            ->whereIn('id', $assignmentIds)
            ->get()
            ->keyBy('id');

        $shiftStr = strtolower($classroom->shift ?? 'morning');

        // ── Gọi Service để kiểm tra toàn bộ ràng buộc ──────────────────────
        $error = $this->validator->validate(
            $schedules,
            $classId,
            $allAssignments,
            $settings,
            $scheduleName,
            $shiftStr
        );

        if ($error) {
            return response()->json(['status' => 'error', 'message' => $error['message']]);
        }

        // ── DB Transaction: Chống mất dữ liệu khi lỗi giữa chừng ───────────
        try {
            DB::transaction(function () use ($scheduleName, $classId, $schedules) {
                // Xóa lịch cũ của lớp trong cùng học kỳ
                Schedule::where('schedule_name', $scheduleName)
                    ->whereHas('assignment', function ($q) use ($classId) {
                        $q->where('class_id', $classId);
                    })
                    ->delete();

                // Tạo lịch mới hàng loạt
                foreach ($schedules as $item) {
                    Schedule::create($item + ['schedule_name' => $scheduleName]);
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi hệ thống khi lưu lịch. Vui lòng thử lại! (' . $e->getMessage() . ')',
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function list()
    {
        $scheduleName  = $this->getScheduleName();
        $classes       = Classroom::orderBy('grade')->orderBy('name')->get();
        $groupedClasses = $classes->groupBy('grade');
        $teachers      = Teacher::orderBy('name')->get();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->with(['assignment.subject', 'assignment.teacher', 'assignment.classroom', 'room'])
            ->get();

        $settings = Setting::pluck('value', 'key')->all();

        return view('admin.schedules.list', compact('groupedClasses', 'classes', 'teachers', 'schedules', 'settings'));
    }

    public function show($class_id)
    {
        $scheduleName = $this->getScheduleName();
        $classroom    = Classroom::findOrFail($class_id);
        $settings     = Setting::pluck('value', 'key')->all();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->whereHas('assignment', fn($q) => $q->where('class_id', $class_id))
            ->with(['assignment.subject', 'assignment.teacher', 'room'])
            ->get();

        return view('admin.schedules.show', compact('classroom', 'schedules', 'settings'));
    }

    public function printAll()
    {
        $scheduleName   = $this->getScheduleName();
        $classes        = Classroom::orderBy('grade')->orderBy('name')->get();
        $groupedClasses = $classes->groupBy('grade');
        $settings       = Setting::pluck('value', 'key')->all();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->with(['assignment.subject', 'assignment.teacher', 'assignment.classroom', 'room'])
            ->get();

        return view('admin.schedules.list', compact('groupedClasses', 'classes', 'schedules', 'settings'));
    }
}