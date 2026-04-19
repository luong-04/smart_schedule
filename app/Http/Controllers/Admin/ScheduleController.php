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
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    /**
     * Khởi tạo Controller với các dịch vụ bổ trợ.
     * 
     * @param ScheduleValidationService $validator Dịch vụ kiểm tra tính hợp lệ của thời khóa biểu.
     * @param ScheduleDataService $dataService Dịch vụ cung cấp dữ liệu cho thời khóa biểu.
     */
    public function __construct(
        private ScheduleValidationService $validator,
        private ScheduleDataService $dataService
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────────────────

    private static ?string $cachedScheduleName = null;

    /**
     * Lấy tên bản thời khóa biểu hiện tại (được cấu hình trong cài đặt hoặc lấy bản mới nhất).
     * 
     * @return string Tên bản thời khóa biểu.
     */
    private function getScheduleName(): string
    {
        if (self::$cachedScheduleName) {
            return self::$cachedScheduleName;
        }

        $configured = Setting::getVal('active_schedule');
        if ($configured && Schedule::where('schedule_name', $configured)->exists()) {
            $name = $configured;
            if (app()->environment('testing'))
                return $name;
            return self::$cachedScheduleName = $name;
        }

        $semester = Setting::getVal('semester', 'Học kỳ 1');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        $builtName = $semester . ' - ' . $schoolYear;

        if (Schedule::where('schedule_name', $builtName)->exists()) {
            return self::$cachedScheduleName = $builtName;
        }

        $latest = Schedule::latest('updated_at')->first();
        if ($latest) {
            return self::$cachedScheduleName = $latest->schedule_name;
        }

        return self::$cachedScheduleName = $builtName;
    }

    /**
     * Lấy các biến cấu hình buổi học (Chào cờ, Sinh hoạt lớp) dựa trên buổi của lớp học.
     * 
     * @param Classroom $classroom Đối tượng lớp học.
     * @param array $settings Mảng các cài đặt hệ thống.
     * @return array Mảng các biến cấu hình buổi học.
     */
    private function getShiftVars(Classroom $classroom, array $settings): array
    {
        $shiftStr = strtolower($classroom->shift ?? 'morning');
        $isMorning = ($shiftStr === 'morning');

        $flagDay = $settings[$shiftStr . '_flag_day'] ?? Setting::DEFAULT_FLAG_DAY;
        $flagPeriod = $settings[$shiftStr . '_flag_period'] ?? ($isMorning ? Setting::DEFAULT_FLAG_PER_M : Setting::DEFAULT_FLAG_PER_A);
        $meetDay = $settings[$shiftStr . '_meeting_day'] ?? Setting::DEFAULT_MEET_DAY;
        $meetPeriod = $settings[$shiftStr . '_meeting_period'] ?? ($isMorning ? Setting::DEFAULT_MEET_PER_M : Setting::DEFAULT_MEET_PER_A);

        return compact('shiftStr', 'flagDay', 'flagPeriod', 'meetDay', 'meetPeriod');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hiển thị trang dashboard của quản trị viên với các số liệu thống kê.
     * 
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $scheduleName = $this->getScheduleName();

        $stats = [
            'teachers' => Teacher::count(),
            'classrooms' => Classroom::count(),
            'rooms' => Room::count(),
            'assignments' => Assignment::count(),
        ];

        // Tối ưu: Lấy 5 ID lớp có thay đổi gần nhất bằng truy vấn SQL thay vì tải toàn bộ TKB vào memory
        $recentClassIds = Schedule::where('schedule_name', $scheduleName)
            ->select('class_id')
            ->orderBy('updated_at', 'desc')
            ->distinct()
            ->limit(6)
            ->pluck('class_id');

        $recentSchedules = collect();
        foreach ($recentClassIds as $cId) {
            $s = Schedule::where('schedule_name', $scheduleName)
                ->where('class_id', $cId)
                ->with(['assignment.classroom.homeroomTeacher', 'assignment.teacher', 'assignment.subject'])
                ->latest('updated_at')
                ->first();
            if ($s)
                $recentSchedules->push($s);
        }

        // Tính tổng số lớp ĐÃ có ít nhất 1 tiết trong bản TKB hiện tại
        $scheduledCount = Schedule::where('schedule_name', $scheduleName)
            ->distinct('class_id')
            ->count('class_id');

        return view('admin.dashboard', compact('stats', 'recentSchedules', 'scheduledCount'));
    }

    /**
     * Hiển thị trang lập thời khóa biểu (Ma trận) cho một lớp học cụ thể.
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        $scheduleName = $this->getScheduleName();
        $classes = Classroom::orderBy('grade', 'asc')->orderBy('name', 'asc')->get();

        if ($classes->isEmpty()) {
            return redirect()->route('classrooms.index')
                ->with('error', 'Cần tạo lớp học trước!');
        }

        $selectedClassId = $request->input('class_id', $classes->first()?->id);
        // Tối ưu N+1: Eager load homeroomTeacher
        $classroom = Classroom::with('homeroomTeacher')->findOrFail($selectedClassId);
        $settings = Setting::pluck('value', 'key')->all();
        $rooms = Room::all();
        $shiftVars = $this->getShiftVars($classroom, $settings);

        $currentDate = $request->input('date', now()->toDateString());

        $historyRanges = Schedule::where('schedule_name', $scheduleName)
            ->where('class_id', $selectedClassId)
            ->select('applies_from', 'applies_to')
            ->distinct()
            ->orderBy('applies_from', 'desc')
            ->get();

        // Fix logic Jump Back: Nếu có ?date cụ thể, ưu tiên tìm range chứa date đó
        $activeRange = null;
        if ($request->has('date')) {
            $lookDate = $request->input('date');
            $activeRange = $historyRanges->filter(function ($r) use ($lookDate) {
                $from = $r->applies_from instanceof Carbon ? $r->applies_from->toDateString() : (string) $r->applies_from;
                $to = $r->applies_to instanceof Carbon ? $r->applies_to->toDateString() : (string) $r->applies_to;
                return $from && $to && $lookDate >= $from && $lookDate <= $to;
            })->first();
        }

        if (!$activeRange) {
            $activeRange = $historyRanges->filter(function ($r) use ($currentDate) {
                $from = $r->applies_from instanceof Carbon ? $r->applies_from->toDateString() : (string) $r->applies_from;
                $to = $r->applies_to instanceof Carbon ? $r->applies_to->toDateString() : (string) $r->applies_to;
                return $from && $to && $currentDate >= $from && $currentDate <= $to;
            })->first() ?? $historyRanges->first();
        }

        $appliesFrom = ($activeRange && $activeRange->applies_from) ? ($activeRange->applies_from instanceof Carbon ? $activeRange->applies_from->toDateString() : (string) $activeRange->applies_from) : now()->startOfWeek()->toDateString();
        $appliesTo = ($activeRange && $activeRange->applies_to) ? ($activeRange->applies_to instanceof Carbon ? $activeRange->applies_to->toDateString() : (string) $activeRange->applies_to) : now()->startOfWeek()->addDays(6)->toDateString();

        $allAssignments = Assignment::with(['teacher', 'subject'])->where('class_id', $selectedClassId)->get();
        $curriculums = SubjectConfiguration::where('grade', $classroom->grade)->where('block', $classroom->block_name)->pluck('slots_per_week', 'subject_id')->all();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->where('class_id', $selectedClassId)
            ->where('applies_from', $appliesFrom)
            ->with(['assignment.subject', 'assignment.teacher', 'room'])
            ->get()
            ->keyBy(fn($s) => "{$s->day_of_week}-{$s->period}");

        [$teacherBusySlots, $teacherOtherDays, $roomBusySlots] = $this->dataService->getBusySlots($scheduleName, $selectedClassId, $appliesFrom);
        $counts = $this->dataService->getUsedCounts($scheduleName, $allAssignments, $appliesFrom);
        $assignments = $this->dataService->buildValidAssignments($allAssignments, $curriculums, $counts, $settings);

        return view('admin.schedules.index', compact(
            'classes',
            'assignments',
            'schedules',
            'selectedClassId',
            'classroom',
            'settings',
            'rooms',
            'teacherBusySlots',
            'teacherOtherDays',
            'roomBusySlots',
            'shiftVars',
            'appliesFrom',
            'appliesTo',
            'historyRanges'
        ));
    }

    /**
     * Lưu thông tin thời khóa biểu của một lớp cho một phiên bản (ngày áp dụng).
     * 
     * @param StoreScheduleRequest $request Request chứa dữ liệu thời khóa biểu.
     * @return \Illuminate\Http\JsonResponse Kết quả lưu dạng JSON.
     */
    public function save(StoreScheduleRequest $request)
    {
        $scheduleName = $this->getScheduleName();
        $classId = $request->input('class_id');
        $schedules = $request->input('schedules');
        $lastUpdated = $request->input('last_updated_at');

        $classroom = Classroom::findOrFail($classId);
        $settings = Setting::pluck('value', 'key')->all();

        try {
            if ($lastUpdated) {
                $lastUpdatedTime = Carbon::parse($lastUpdated);
                if ($classroom->updated_at->gt($lastUpdatedTime)) {
                    return response()->json(['status' => 'error', 'message' => 'Dữ liệu đã bị thay đổi bởi người khác! Vui lòng tải lại trang.'], 422);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Schedule Save Conflict Check Error: " . $e->getMessage());
        }

        $assignmentIds = collect($schedules)->pluck('assignment_id')->unique()->toArray();
        $allAssignments = Assignment::with(['teacher', 'subject', 'classroom'])->whereIn('id', $assignmentIds)->get()->keyBy('id');

        $shiftStr = strtolower($classroom->shift ?? 'morning');
        $appliesFrom = $request->input('applies_from');
        $appliesTo = $request->input('applies_to');

        if (!$appliesFrom || !$appliesTo) {
            return response()->json(['status' => 'error', 'message' => 'Thiếu ngày áp dụng!'], 422);
        }

        $curriculums = SubjectConfiguration::where('grade', $classroom->grade)->where('block', $classroom->block_name)->pluck('slots_per_week', 'subject_id')->all();
        [$teacherBusySlots, $teacherOtherDays, $roomBusySlots] = $this->dataService->getBusySlots($scheduleName, $classId, $appliesFrom);
        $busyData = compact('teacherBusySlots', 'teacherOtherDays', 'roomBusySlots');

        try {
            $error = $this->validator->validate($schedules, $classroom, $allAssignments, $settings, $scheduleName, $shiftStr, $busyData, $curriculums);
            if ($error)
                return response()->json(['status' => 'error', 'message' => $error['message']], 422);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Lỗi kiểm tra: ' . $e->getMessage()], 500);
        }

        try {
            DB::transaction(function () use ($scheduleName, $classId, $schedules, $allAssignments, $classroom, $appliesFrom, $appliesTo) {
                // 1. Xóa toàn bộ lịch cũ của lớp này trong version hiện tại
                Schedule::where('schedule_name', $scheduleName)
                    ->where('class_id', $classId)
                    ->where('applies_from', $appliesFrom)
                    ->delete();

                if (empty($schedules)) {
                    $classroom->touch();
                    return;
                }

                // 2. Chuẩn bị dữ liệu Bulk Insert
                $now = now();
                $insertData = [];

                foreach ($schedules as $item) {
                    $realAssignment = $allAssignments->get($item['assignment_id']);
                    if (!$realAssignment)
                        continue;

                    $insertData[] = [
                        'assignment_id' => $item['assignment_id'],
                        'day_of_week' => (int) $item['day_of_week'],
                        'period' => (int) $item['period'],
                        'room_id' => $item['room_id'] ?? null,
                        'teacher_id' => $realAssignment->teacher_id,
                        'class_id' => $classId,
                        'schedule_name' => $scheduleName,
                        'applies_from' => $appliesFrom,
                        'applies_to' => $appliesTo,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // 3. Thực hiện Bulk Insert duy nhất 1 query
                if (!empty($insertData)) {
                    Schedule::insert($insertData);
                }

                $classroom->touch();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? 0;
            $msg = $e->getMessage();

            // 1062 = Duplicate entry (MySQL)
            if ($errorCode === 1062) {
                if (strpos($msg, 'teacher_slot_unique_v3') !== false) {
                    return response()->json(['status' => 'error', 'message' => 'Trùng lịch giáo viên: Giáo viên này đã có tiết dạy ở lớp khác vào thời điểm này!'], 422);
                }
                if (strpos($msg, 'room_slot_unique_v3') !== false) {
                    return response()->json(['status' => 'error', 'message' => 'Trùng phòng học: Phòng học này đã được sử dụng bởi lớp khác vào thời điểm này!'], 422);
                }
                if (strpos($msg, 'class_slot_unique_v3') !== false) {
                    return response()->json(['status' => 'error', 'message' => 'Lỗi dữ liệu: Lớp học này đã có tiết khác tại thời điểm đang lưu!'], 422);
                }
            }

            // Fallback cho SQLite hoặc các lỗi khác (Giữ lại tính tương thích ngược)
            if (strpos($msg, 'UNIQUE constraint failed') !== false) {
                if (strpos($msg, 'teacher_id') !== false)
                    return response()->json(['status' => 'error', 'message' => 'Trùng lịch giáo viên (SQLite)!'], 422);
                if (strpos($msg, 'room_id') !== false)
                    return response()->json(['status' => 'error', 'message' => 'Trùng phòng học (SQLite)!'], 422);
                if (strpos($msg, 'class_id') !== false)
                    return response()->json(['status' => 'error', 'message' => 'Lỗi dữ liệu lớp học (SQLite)!'], 422);
            }

            return response()->json(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu: ' . $msg], 500);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'status' => 'success',
            'last_updated_at' => $classroom->updated_at->toDateTimeString()
        ]);
    }

    /**
     * Hiển thị danh sách tổng hợp thời khóa biểu của toàn trường.
     * Tối ưu hóa tải dữ liệu lớn bằng cách pre-aggregate (tổng hợp trước).
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function list(Request $request)
    {
        $scheduleName = $this->getScheduleName();
        $classes = Classroom::orderBy('grade')->orderBy('name')->get();
        $groupedClasses = $classes->groupBy('grade');
        $teachers = Teacher::orderBy('name')->get();
        $settings = Setting::pluck('value', 'key')->all();
        $currentDate = $request->input('date', now()->toDateString());

        $historyRanges = Schedule::where('schedule_name', $scheduleName)
            ->select('applies_from', 'applies_to')
            ->distinct()
            ->orderBy('applies_from', 'desc')
            ->get();

        // Tối ưu Logic Version Jump: 
        // 1. Ưu tiên TUYỆT ĐỐI ?date từ dropdown (chọn version cụ thể)
        // 2. Tiếp theo mới đến ?lookup_date từ picker (chọn ngày xem)
        // 3. Cuối cùng là ngày hiện tại
        $lookDate = $request->input('date');
        $pickerDate = $request->input('lookup_date');

        $activeRange = null;
        if ($lookDate) {
            $activeRange = $historyRanges->first(fn($r) => (optional($r->applies_from)->toDateString() == $lookDate));
        }

        if (!$activeRange && $pickerDate) {
            $activeRange = $historyRanges->filter(function ($r) use ($pickerDate) {
                $from = optional($r->applies_from)->toDateString();
                $to = optional($r->applies_to)->toDateString();
                return $from && $to && $pickerDate >= $from && $pickerDate <= $to;
            })->first();
        }

        if (!$activeRange) {
            $activeRange = $historyRanges->filter(function ($r) use ($currentDate) {
                $from = optional($r->applies_from)->toDateString();
                $to = optional($r->applies_to)->toDateString();
                return $from && $to && $currentDate >= $from && $currentDate <= $to;
            })->first() ?? $historyRanges->first();
        }

        $appliesFrom = ($activeRange && $activeRange->applies_from) ? ($activeRange->applies_from instanceof Carbon ? $activeRange->applies_from->toDateString() : (string) $activeRange->applies_from) : now()->startOfWeek()->toDateString();
        $appliesTo = ($activeRange && $activeRange->applies_to) ? ($activeRange->applies_to instanceof Carbon ? $activeRange->applies_to->toDateString() : (string) $activeRange->applies_to) : now()->startOfWeek()->addDays(6)->toDateString();

        // 🚀 TỐI ƯU HIỆU SUẤT TRANG DANH SÁCH 🚀
        // 1. Pre-aggregate TKB: Group theo teacher_id, day, period
        $rawSchedules = Schedule::where('schedule_name', $scheduleName)
            ->where('applies_from', $appliesFrom)
            ->with(['assignment.subject', 'assignment.teacher', 'assignment.classroom', 'room'])
            ->get();

        $teacherSchedules = [];
        $classSchedules = [];
        foreach ($rawSchedules as $s) {
            $teacherSchedules[$s->teacher_id][$s->day_of_week][$s->period] = $s;
            $classSchedules[$s->class_id][$s->day_of_week][$s->period] = $s;
        }

        // 2. Map Homeroom Teachers: Giúp View tìm GVCN của lớp cực nhanh (O(1))
        $homeroomMap = $classes->keyBy('homeroom_teacher_id');

        return view('admin.schedules.list', compact(
            'groupedClasses',
            'classes',
            'teachers',
            'settings',
            'appliesFrom',
            'appliesTo',
            'historyRanges',
            'teacherSchedules',
            'classSchedules',
            'homeroomMap'
        ));
    }

    /**
     * Xem chi tiết thời khóa biểu của một lớp dưới dạng bảng tĩnh (read-only).
     * 
     * @param Request $request
     * @param int $class_id ID của lớp học.
     * @return \Illuminate\View\View
     */
    public function show(Request $request, $class_id)
    {
        $scheduleName = $this->getScheduleName();
        $classroom = Classroom::findOrFail($class_id);
        $settings = Setting::pluck('value', 'key')->all();
        $currentDate = $request->input('date', now()->toDateString());

        $historyRanges = Schedule::where('schedule_name', $scheduleName)
            ->where('class_id', $class_id)
            ->select('applies_from', 'applies_to')
            ->distinct()
            ->orderBy('applies_from', 'desc')
            ->get();

        $activeRange = $historyRanges->filter(function ($r) use ($currentDate) {
            $from = $r->applies_from instanceof Carbon ? $r->applies_from->toDateString() : (string) $r->applies_from;
            $to = $r->applies_to instanceof Carbon ? $r->applies_to->toDateString() : (string) $r->applies_to;
            return $from && $to && $currentDate >= $from && $currentDate <= $to;
        })->first() ?? $historyRanges->first();

        $appliesFrom = ($activeRange && $activeRange->applies_from) ? ($activeRange->applies_from instanceof Carbon ? $activeRange->applies_from->toDateString() : (string) $activeRange->applies_from) : now()->startOfWeek()->toDateString();
        $appliesTo = ($activeRange && $activeRange->applies_to) ? ($activeRange->applies_to instanceof Carbon ? $activeRange->applies_to->toDateString() : (string) $activeRange->applies_to) : now()->startOfWeek()->addDays(6)->toDateString();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->where('applies_from', $appliesFrom)
            ->whereHas('assignment', fn($q) => $q->where('class_id', $class_id))
            ->with(['assignment.subject', 'assignment.teacher', 'room'])
            ->get()
            ->keyBy(fn($s) => "{$s->day_of_week}-{$s->period}");

        return view('admin.schedules.show', compact('classroom', 'schedules', 'settings', 'appliesFrom', 'appliesTo'));
    }

    /**
     * Tạo dữ liệu in ấn cho tất cả các lớp (tái sử dụng logic của hàm list).
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function printAll(Request $request)
    {
        // Re-use logic từ list() cho printAll để đảm bảo hiệu suất và đồng bộ
        return $this->list($request);
    }
}