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
    public function __construct(
        private ScheduleValidationService $validator,
        private ScheduleDataService $dataService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────────────────

    private static ?string $cachedScheduleName = null;

    private function getScheduleName(): string
    {
        if (self::$cachedScheduleName) {
            return self::$cachedScheduleName;
        }

        $configured = Setting::getVal('active_schedule');
        if ($configured && Schedule::where('schedule_name', $configured)->exists()) {
            return self::$cachedScheduleName = $configured;
        }

        $semester   = Setting::getVal('semester', 'Học kỳ 1');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        $builtName  = $semester . ' - ' . $schoolYear;
        
        if (Schedule::where('schedule_name', $builtName)->exists()) {
            return self::$cachedScheduleName = $builtName;
        }

        $latest = Schedule::latest('updated_at')->first();
        if ($latest) {
            return self::$cachedScheduleName = $latest->schedule_name;
        }

        return self::$cachedScheduleName = $builtName;
    }

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
                ->with('error', 'Cần tạo lớp học trước!');
        }

        $selectedClassId = $request->input('class_id', $classes->first()?->id);
        // Tối ưu N+1: Eager load homeroomTeacher
        $classroom       = Classroom::with('homeroomTeacher')->findOrFail($selectedClassId);
        $settings        = Setting::pluck('value', 'key')->all();
        $rooms           = Room::all();
        $shiftVars       = $this->getShiftVars($classroom, $settings);

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
            $activeRange = $historyRanges->filter(function($r) use ($lookDate) {
                $from = $r->applies_from instanceof Carbon ? $r->applies_from->toDateString() : (string)$r->applies_from;
                $to   = $r->applies_to instanceof Carbon ? $r->applies_to->toDateString() : (string)$r->applies_to;
                return $from && $to && $lookDate >= $from && $lookDate <= $to;
            })->first();
        }
        
        if (!$activeRange) {
            $activeRange = $historyRanges->filter(function($r) use ($currentDate) {
                $from = $r->applies_from instanceof Carbon ? $r->applies_from->toDateString() : (string)$r->applies_from;
                $to   = $r->applies_to instanceof Carbon ? $r->applies_to->toDateString() : (string)$r->applies_to;
                return $from && $to && $currentDate >= $from && $currentDate <= $to;
            })->first() ?? $historyRanges->first();
        }

        $appliesFrom = ($activeRange && $activeRange->applies_from) ? ($activeRange->applies_from instanceof Carbon ? $activeRange->applies_from->toDateString() : (string)$activeRange->applies_from) : now()->startOfWeek()->toDateString();
        $appliesTo   = ($activeRange && $activeRange->applies_to)   ? ($activeRange->applies_to instanceof Carbon ? $activeRange->applies_to->toDateString() : (string)$activeRange->applies_to) : now()->startOfWeek()->addDays(6)->toDateString();

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
            'classes', 'assignments', 'schedules', 'selectedClassId', 'classroom',
            'settings', 'rooms', 'teacherBusySlots', 'teacherOtherDays', 'roomBusySlots',
            'shiftVars', 'appliesFrom', 'appliesTo', 'historyRanges'
        ));
    }

    public function save(StoreScheduleRequest $request)
    {
        $scheduleName = $this->getScheduleName();
        $classId      = $request->input('class_id');
        $schedules    = $request->input('schedules');
        $lastUpdated  = $request->input('last_updated_at');
        
        $classroom    = Classroom::findOrFail($classId);
        $settings     = Setting::pluck('value', 'key')->all();

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

        $assignmentIds  = collect($schedules)->pluck('assignment_id')->unique()->toArray();
        $allAssignments = Assignment::with(['teacher', 'subject', 'classroom'])->whereIn('id', $assignmentIds)->get()->keyBy('id');

        $shiftStr = strtolower($classroom->shift ?? 'morning');
        $appliesFrom = $request->input('applies_from');
        $appliesTo   = $request->input('applies_to');

        if (!$appliesFrom || !$appliesTo) {
            return response()->json(['status' => 'error', 'message' => 'Thiếu ngày áp dụng!'], 422);
        }

        $curriculums = SubjectConfiguration::where('grade', $classroom->grade)->where('block', $classroom->block_name)->pluck('slots_per_week', 'subject_id')->all();
        [$teacherBusySlots, $teacherOtherDays, $roomBusySlots] = $this->dataService->getBusySlots($scheduleName, $classId, $appliesFrom);
        $busyData = compact('teacherBusySlots', 'teacherOtherDays', 'roomBusySlots');

        try {
            $error = $this->validator->validate($schedules, $classroom, $allAssignments, $settings, $scheduleName, $shiftStr, $busyData, $curriculums);
            if ($error) return response()->json(['status' => 'error', 'message' => $error['message']], 422);
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
                    if (!$realAssignment) continue;

                    $insertData[] = [
                        'assignment_id' => $item['assignment_id'],
                        'day_of_week'   => (int) $item['day_of_week'],
                        'period'        => (int) $item['period'],
                        'room_id'       => $item['room_id'] ?? null,
                        'teacher_id'    => $realAssignment->teacher_id,
                        'class_id'      => $classId,
                        'schedule_name' => $scheduleName,
                        'applies_from'  => $appliesFrom,
                        'applies_to'    => $appliesTo,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                // 3. Thực hiện Bulk Insert duy nhất 1 query
                if (!empty($insertData)) {
                    Schedule::insert($insertData);
                }

                $classroom->touch();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->getCode();
            $msg = $e->getMessage();

            if (strpos($msg, 'teacher_slot_unique_v3') !== false) {
                return response()->json(['status' => 'error', 'message' => 'Trùng lịch giáo viên: Giáo viên này đã có tiết dạy lớp khác vào thời gian này!'], 422);
            }
            if (strpos($msg, 'room_slot_unique_v3') !== false) {
                return response()->json(['status' => 'error', 'message' => 'Trùng phòng học: Phòng này đã được lớp khác sử dụng vào thời gian này!'], 422);
            }
            if (strpos($msg, 'class_slot_unique_v3') !== false) {
                return response()->json(['status' => 'error', 'message' => 'Trùng lịch lớp: Tiết học này đã được xếp môn khác cho lớp!'], 422);
            }

            return response()->json(['status' => 'error', 'message' => 'Lỗi DB: ' . $msg], 500);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'status' => 'success',
            'last_updated_at' => $classroom->updated_at->toDateTimeString()
        ]);
    }

    public function list(Request $request)
    {
        $scheduleName   = $this->getScheduleName();
        $classes        = Classroom::orderBy('grade')->orderBy('name')->get();
        $groupedClasses = $classes->groupBy('grade');
        $teachers       = Teacher::orderBy('name')->get();
        $settings       = Setting::pluck('value', 'key')->all();
        $currentDate    = $request->input('date', now()->toDateString());
        
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
            $activeRange = $historyRanges->filter(function($r) use ($pickerDate) {
                $from = optional($r->applies_from)->toDateString();
                $to   = optional($r->applies_to)->toDateString();
                return $from && $to && $pickerDate >= $from && $pickerDate <= $to;
            })->first();
        }

        if (!$activeRange) {
            $activeRange = $historyRanges->filter(function($r) use ($currentDate) {
                $from = optional($r->applies_from)->toDateString();
                $to   = optional($r->applies_to)->toDateString();
                return $from && $to && $currentDate >= $from && $currentDate <= $to;
            })->first() ?? $historyRanges->first();
        }

        $appliesFrom = ($activeRange && $activeRange->applies_from) ? ($activeRange->applies_from instanceof Carbon ? $activeRange->applies_from->toDateString() : (string)$activeRange->applies_from) : now()->startOfWeek()->toDateString();
        $appliesTo   = ($activeRange && $activeRange->applies_to)   ? ($activeRange->applies_to instanceof Carbon ? $activeRange->applies_to->toDateString() : (string)$activeRange->applies_to) : now()->startOfWeek()->addDays(6)->toDateString();

        // 🚀 TỐI ƯU HIỆU SUẤT TRANG DANH SÁCH 🚀
        // 1. Pre-aggregate TKB: Group theo teacher_id, day, period
        $rawSchedules = Schedule::where('schedule_name', $scheduleName)
            ->where('applies_from', $appliesFrom)
            ->with(['assignment.subject', 'assignment.teacher', 'assignment.classroom', 'room'])
            ->get();

        $teacherSchedules = [];
        $classSchedules   = [];
        foreach ($rawSchedules as $s) {
            $teacherSchedules[$s->teacher_id][$s->day_of_week][$s->period] = $s;
            $classSchedules[$s->class_id][$s->day_of_week][$s->period] = $s;
        }

        // 2. Map Homeroom Teachers: Giúp View tìm GVCN của lớp cực nhanh (O(1))
        $homeroomMap = $classes->keyBy('homeroom_teacher_id');

        return view('admin.schedules.list', compact(
            'groupedClasses', 'classes', 'teachers', 'settings', 
            'appliesFrom', 'appliesTo', 'historyRanges',
            'teacherSchedules', 'classSchedules', 'homeroomMap'
        ));
    }

    public function show(Request $request, $class_id)
    {
        $scheduleName = $this->getScheduleName();
        $classroom    = Classroom::findOrFail($class_id);
        $settings     = Setting::pluck('value', 'key')->all();
        $currentDate  = $request->input('date', now()->toDateString());

        $historyRanges = Schedule::where('schedule_name', $scheduleName)
            ->where('class_id', $class_id)
            ->select('applies_from', 'applies_to')
            ->distinct()
            ->orderBy('applies_from', 'desc')
            ->get();

        $activeRange = $historyRanges->filter(function($r) use ($currentDate) {
            $from = $r->applies_from instanceof Carbon ? $r->applies_from->toDateString() : (string)$r->applies_from;
            $to   = $r->applies_to instanceof Carbon ? $r->applies_to->toDateString() : (string)$r->applies_to;
            return $from && $to && $currentDate >= $from && $currentDate <= $to;
        })->first() ?? $historyRanges->first();

        $appliesFrom = ($activeRange && $activeRange->applies_from) ? ($activeRange->applies_from instanceof Carbon ? $activeRange->applies_from->toDateString() : (string)$activeRange->applies_from) : now()->startOfWeek()->toDateString();
        $appliesTo   = ($activeRange && $activeRange->applies_to)   ? ($activeRange->applies_to instanceof Carbon ? $activeRange->applies_to->toDateString() : (string)$activeRange->applies_to) : now()->startOfWeek()->addDays(6)->toDateString();

        $schedules = Schedule::where('schedule_name', $scheduleName)
            ->where('applies_from', $appliesFrom)
            ->whereHas('assignment', fn($q) => $q->where('class_id', $class_id))
            ->with(['assignment.subject', 'assignment.teacher', 'room'])
            ->get()
            ->keyBy(fn($s) => "{$s->day_of_week}-{$s->period}");

        return view('admin.schedules.show', compact('classroom', 'schedules', 'settings', 'appliesFrom', 'appliesTo'));
    }

    public function printAll(Request $request)
    {
        // Re-use logic từ list() cho printAll để đảm bảo hiệu suất và đồng bộ
        return $this->list($request);
    }
}