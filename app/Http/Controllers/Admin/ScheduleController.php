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
        $lastUpdated  = $request->input('last_updated_at'); // Optimistic Locking
        
        $classroom    = Classroom::findOrFail($classId);
        $settings     = Setting::pluck('value', 'key')->all();

        // 1. Kiểm tra Optimistic Locking: Chống ghi đè dữ liệu cũ
        if ($lastUpdated && $classroom->updated_at->gt($lastUpdated)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Dữ liệu đã bị thay đổi bởi người khác. Vui lòng tải lại trang để thấy bản cập nhật mới nhất!',
            ], 422);
        }

        // 2. Chống IDOR — tất cả assignment_id phải thuộc class_id
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

        // Pre-load dữ liệu bận từ các lớp khác để validate (Memory-based)
        [$teacherBusySlots, $teacherOtherDays, $roomBusySlots] = $this->dataService->getBusySlots($scheduleName, $classId);
        $busyData = compact('teacherBusySlots', 'teacherOtherDays', 'roomBusySlots');

        // ── 3. Gọi Service để kiểm tra toàn bộ ràng buộc ──────────────────
        $error = $this->validator->validate(
            $schedules,
            $classroom,
            $allAssignments,
            $settings,
            $scheduleName,
            $shiftStr,
            $busyData
        );

        if ($error) {
            return response()->json(['status' => 'error', 'message' => $error['message']]);
        }

        // ── 4. DB Transaction: Diff-Update (Upsert) ──────────────────────
        try {
            DB::transaction(function () use ($scheduleName, $classId, $schedules, $allAssignments, $classroom) {
                // Lấy lịch hiện tại của lớp
                $existingSchedules = Schedule::where('schedule_name', $scheduleName)
                    ->whereHas('assignment', function ($q) use ($classId) {
                        $q->where('class_id', $classId);
                    })
                    ->lockForUpdate()
                    ->get();
                
                $keepIds = [];

                foreach ($schedules as $item) {
                    $d = (int) $item['day_of_week'];
                    $p = (int) $item['period'];
                    
                    // Tìm xem tiết này đã có trong DB chưa
                    $match = $existingSchedules->first(fn($s) => $s->day_of_week == $d && $s->period == $p);
                    
                    $realAssignment = $allAssignments->get($item['assignment_id']);
                    $dataToStore = [
                        'assignment_id' => $item['assignment_id'],
                        'day_of_week'   => $d,
                        'period'        => $p,
                        'room_id'       => $item['room_id'] ?? null,
                        'teacher_id'    => $realAssignment->teacher_id,
                        'schedule_name' => $scheduleName,
                    ];

                    if ($match) {
                        // Nếu đã có, kiểm tra xem có thay đổi gì không (assignment hoặc room)
                        // Nếu không thay đổi, DB sẽ tự bỏ qua update SQL nhờ dirty check của Eloquent
                        $match->update($dataToStore);
                        $keepIds[] = $match->id;
                    } else {
                        // Nếu chưa có, tạo mới
                        $newRecord = Schedule::create($dataToStore);
                        $keepIds[] = $newRecord->id;
                    }
                }

                // Xóa những bản ghi không có trong danh sách mới (Dữ liệu mồ côi)
                Schedule::where('schedule_name', $scheduleName)
                    ->whereHas('assignment', function ($q) use ($classId) {
                        $q->where('class_id', $classId);
                    })
                    ->whereNotIn('id', $keepIds)
                    ->delete();
                
                // Cập nhật timestamp của lớp để trigger Optimistic Locking cho người dùng khác
                $classroom->touch();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Lỗi 23000 là Duplicate entry (vi phạm Unique Constraint ở DB level)
            if ($e->getCode() === '23000') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Dữ liệu bị trùng lặp: Giáo viên hoặc phòng học đã bị đăng ký bởi một thao tác khác cùng lúc.',
                    'errors'  => ['conflict' => ['Vui lòng tải lại trang và kiểm tra lại lịch.']]
                ], 422);
            }
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi hệ thống khi lưu lịch: ' . $e->getMessage(),
            ], 500);
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