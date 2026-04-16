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
use App\Services\ScheduleDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleValidationService $validator,
        private ScheduleDataService $dataService
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
        $isMorning  = ($shiftStr === 'morning');

        $flagDay    = $settings[$shiftStr . '_flag_day']    ?? Setting::DEFAULT_FLAG_DAY;
        $flagPeriod = $settings[$shiftStr . '_flag_period'] ?? ($isMorning ? Setting::DEFAULT_FLAG_PER_M : Setting::DEFAULT_FLAG_PER_A);
        $meetDay    = $settings[$shiftStr . '_meeting_day']    ?? Setting::DEFAULT_MEET_DAY;
        $meetPeriod = $settings[$shiftStr . '_meeting_period'] ?? ($isMorning ? Setting::DEFAULT_MEET_PER_M : Setting::DEFAULT_MEET_PER_A);

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

        // Dùng accessor block_name thay vì thuộc tính trực tiếp để đảm bảo fallback an toàn
        $blockName  = $classroom->block_name;

        $curriculums = SubjectConfiguration::where('grade', $classroom->grade)
            ->where('block', $blockName)
            ->pluck('slots_per_week', 'subject_id')
            ->all();

        [$teacherBusySlots, $teacherOtherDays, $roomBusySlots] = $this->dataService->getBusySlots($scheduleName, $selectedClassId);
        $counts = $this->dataService->getUsedCounts($scheduleName, $allAssignments);
        $assignments = $this->dataService->buildValidAssignments($allAssignments, $curriculums, $counts, $settings);

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

        // Pre-load tất cả assignments 1 lần kèm relations để validation
        $assignmentIds  = collect($schedules)->pluck('assignment_id')->unique()->toArray();
        $allAssignments = Assignment::with(['teacher', 'subject', 'classroom'])
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

        // ── DB Transaction: Chống mất dữ liệu và Race Condition ───────────
        try {
            DB::transaction(function () use ($scheduleName, $classId, $schedules, $allAssignments) {
                // 1. Khóa các lịch hiện tại của lớp ở mức cursor DB để ngăn vừa xóa vừa thêm trùng lặp
                Schedule::where('schedule_name', $scheduleName)
                    ->whereHas('assignment', function ($q) use ($classId) {
                        $q->where('class_id', $classId);
                    })
                    ->lockForUpdate()
                    ->get();

                // 2. Xóa lịch cũ
                Schedule::where('schedule_name', $scheduleName)
                    ->whereHas('assignment', function ($q) use ($classId) {
                        $q->where('class_id', $classId);
                    })
                    ->delete();

                // 3. Tạo lịch mới hàng loạt
                foreach ($schedules as $item) {
                    // Lấy teacher_id trực tiếp từ Assignment đã load từ DB (sync dữ liệu chuẩn)
                    $realAssignment = $allAssignments->get($item['assignment_id']);
                    
                    if ($realAssignment) {
                        Schedule::create($item + [
                            'schedule_name' => $scheduleName,
                            'teacher_id'    => $realAssignment->teacher_id
                        ]);
                    }
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