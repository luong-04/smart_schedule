<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\SubjectConfiguration;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Service Layer cho toàn bộ logic kiểm tra ràng buộc khi lưu lịch.
 */
class ScheduleValidationService
{
    public function __construct(private Room $room) {}

    /**
     * Kiểm tra tất cả ràng buộc cho một tập hợp lịch trước khi lưu.
     *
     * @param array     $schedules       Mảng lịch từ request [{assignment_id, day_of_week, period, room_id}]
     * @param Classroom $classroom       Đối tượng lớp học đang được xếp lịch
     * @param \Illuminate\Support\Collection $allAssignments  Collection assignment đã load sẵn (keyBy id)
     * @param array     $settings        Mảng settings [key => value]
     * @param string    $scheduleName    Tên học kỳ hiện tại
     * @param string    $shiftStr        'morning' hoặc 'afternoon'
     *
     * @return array|null  null nếu hợp lệ, mảng ['message' => '...'] nếu có lỗi
     */
    public function validate(
        array $schedules,
        Classroom $classroom,
        $allAssignments,
        array $settings,
        string $scheduleName,
        string $shiftStr,
        array $busyData = [],
        array $curriculums = []
    ): ?array {
        // Tính toán các tiết cố định từ settings
        $isMorning  = ($shiftStr === 'morning');
        $flagDay    = $settings[$shiftStr . '_flag_day']    ?? Setting::DEFAULT_FLAG_DAY;
        $flagPeriod = $settings[$shiftStr . '_flag_period'] ?? ($isMorning ? Setting::DEFAULT_FLAG_PER_M : Setting::DEFAULT_FLAG_PER_A);
        $meetDay    = $settings[$shiftStr . '_meeting_day']    ?? Setting::DEFAULT_MEET_DAY;
        $meetPeriod = $settings[$shiftStr . '_meeting_period'] ?? ($isMorning ? Setting::DEFAULT_MEET_PER_M : Setting::DEFAULT_MEET_PER_A);

        $maxConsecutive      = (int) ($settings['max_consecutive_slots'] ?? 3);
        $maxDaysPerWeek      = (int) ($settings['max_days_per_week']     ?? 6);
        $checkTeacherConflict = (bool) ($settings['check_teacher_conflict'] ?? false);
        $checkRoomConflict    = (bool) ($settings['check_room_conflict']    ?? false);

        // Lấy dữ liệu pre-loaded
        $teacherBusySlots = $busyData['teacherBusySlots'] ?? [];
        $roomBusySlots    = $busyData['roomBusySlots']    ?? [];
        $teacherOtherDays = $busyData['teacherOtherDays'] ?? [];

        // Map để gom lịch theo giáo viên trong request này: [teacherId => [day => [periods]]]
        $teacherDayPeriods = [];
        // Map đếm số tiết theo assignment trong request: [assignmentId => count]
        $assignmentCounts = [];

        // Load rooms một lần dùng cho check loại phòng
        $allRooms = $this->room->select(['id', 'name', 'room_type_id'])->get()->keyBy('id');

        foreach ($schedules as $item) {
            $assignment = $allAssignments[$item['assignment_id']] ?? null;
            if (!$assignment) {
                return ['message' => 'Phân công không tồn tại trong hệ thống!'];
            }

            // Phòng thủ SoftDeletes: Nếu giáo viên bị xóa, assignment->teacher sẽ null
            if (!$assignment->teacher) {
                $subName = $assignment->subject->name ?? 'Môn học (N/A)';
                return ['message' => "Lỗi dữ liệu: Giáo viên cho môn {$subName} không tồn tại hoặc đã bị xóa!"];
            }

            if (!$assignment->subject) {
                return ['message' => "Lỗi dữ liệu: Môn học trong phân công #{$assignment->id} không tồn tại!"];
            }

            $teacherId = $assignment->teacher_id;
            $d         = (int) $item['day_of_week'];
            $p         = (int) $item['period'];
            $roomId    = $item['room_id'] ?? null;
            $slotKey   = "$d-$p";

            $assignmentCounts[$assignment->id] = ($assignmentCounts[$assignment->id] ?? 0) + 1;

            // ── 1. Kiểm tra tiết cố định (Chào cờ / Sinh hoạt lớp) ──────────
            if ($error = $this->validateFixedPeriods($d, $p, $flagDay, $flagPeriod, $meetDay, $meetPeriod)) {
                return $error;
            }

            // ── 2. Kiểm tra ngày nghỉ của giáo viên ──────────────────────────
            if ($error = $this->validateTeacherOffDay($assignment, $d)) {
                return $error;
            }

            // ── 3. Kiểm tra trùng lịch giáo viên (với lớp khác) ─────────────
            if ($checkTeacherConflict) {
                if ($error = $this->validateTeacherConflictMemory($teacherId, $slotKey, $assignment, $d, $p, $teacherBusySlots)) {
                    return $error;
                }
            }

            // ── 4. Kiểm tra trùng phòng học ───────────────────────────────────
            if ($roomId) {
                if ($checkRoomConflict) {
                    if ($error = $this->validateRoomConflictMemory($roomId, $slotKey, $d, $p, $roomBusySlots, $allRooms)) {
                        return $error;
                    }
                }

                // ── 4.5. Kiểm tra logic "Phòng học đặc thù" ─────────────────────
                $room = $allRooms[$roomId] ?? null;
                if ($room && $assignment->subject->room_type_id && $room->room_type_id !== $assignment->subject->room_type_id) {
                    return [
                        'message' => "Sai loại phòng: Môn {$assignment->subject->name} yêu cầu loại phòng đặc thù, không thể học tại phòng {$room->name}!"
                    ];
                }
            }

            // Gom lịch theo giáo viên để kiểm tra tiết liên tiếp và số ngày
            $teacherDayPeriods[$teacherId][$d][] = $p;
        }

        // ── 5. Kiểm tra vượt định mức tiết môn ───────────────────────────────
        if ($error = $this->validateSlotLimits($assignmentCounts, $allAssignments, $classroom, $settings, $curriculums)) {
            return $error;
        }

        // ── 6. Kiểm tra tiết liên tiếp + số ngày dạy tối đa ─────────────────
        foreach ($teacherDayPeriods as $tId => $days) {
            if ($error = $this->validateMaxDaysMemory($tId, $days, $allAssignments, $maxDaysPerWeek, $teacherOtherDays)) {
                return $error;
            }
            if ($error = $this->validateConsecutiveSlots($tId, $days, $allAssignments, $maxConsecutive)) {
                return $error;
            }
            if ($error = $this->validateGapSession($tId, $days, $allAssignments, 'giáo viên')) {
                return $error;
            }
        }

        // Đảm bảo không có tiết trống trong lịch học của lớp
        $classDayPeriods = [];
        foreach ($teacherDayPeriods as $tId => $days) {
            foreach ($days as $day => $periods) {
                foreach ($periods as $p) {
                    $classDayPeriods[$day][] = $p;
                }
            }
        }
        foreach ($classDayPeriods as $day => $periods) {
            if ($error = $this->validateGapSession($classroom->id, [$day => $periods], null, 'lớp học')) {
                return $error;
            }
        }

        // Kiểm tra tổng định mức tuần của Giáo viên
        if ($error = $this->validateTeacherWeeklyLimit($teacherDayPeriods, $allAssignments, $settings, $teacherBusySlots)) {
            return $error;
        }

        return null;
    }

    /**
     * Xác thực định mức tiết dạy tuần của giáo viên (tổng tiết hiện có + tiết gán từ GVCN).
     */
    private function validateTeacherWeeklyLimit(array $teacherDayPeriods, $allAssignments, array $settings, array $teacherBusySlots): ?array
    {
        $assignFlag    = (bool) ($settings['assign_gvcn_flag_salute']    ?? false);
        $assignMeeting = (bool) ($settings['assign_gvcn_class_meeting']  ?? false);

        $teacherIds = array_keys($teacherDayPeriods);
        
        // Lấy thông tin GVCN (Số lớp chủ nhiệm của từng GV trong request)
        $gvcnCounts = Classroom::whereIn('homeroom_teacher_id', $teacherIds)
            ->select('homeroom_teacher_id', DB::raw('count(*) as count'))
            ->groupBy('homeroom_teacher_id')
            ->pluck('count', 'homeroom_teacher_id')
            ->all();

        foreach ($teacherDayPeriods as $tId => $days) {
            // 1. Lấy thông tin giáo viên
            $teacher = $allAssignments->first(fn($as) => $as->teacher_id == $tId)?->teacher;
            if (!$teacher) continue;

            $maxSlots = $teacher->max_slots_week;

            // 2. Tính số tiết ở các lớp khác (đã load trong teacherBusySlots)
            $usedOther = count($teacherBusySlots[$tId] ?? []);

            // 3. Tính số tiết ở lớp hiện tại (đang trong request)
            $usedHere = 0;
            foreach ($days as $dayPeriods) {
                $usedHere += count($dayPeriods);
            }

            // 4. Tính số tiết cộng thêm từ nhiệm vụ GVCN (Theo Setting)
            $gvcnBonus = 0;
            $classCount = $gvcnCounts[$tId] ?? 0;
            if ($classCount > 0) {
                if ($assignFlag)    $gvcnBonus += $classCount;
                if ($assignMeeting) $gvcnBonus += $classCount;
            }

            $totalUsed = $usedOther + $usedHere + $gvcnBonus;

            if ($totalUsed > $maxSlots) {
                return [
                    'message' => "Vượt định mức tuần: GV {$teacher->name} được phân tổng cộng {$totalUsed} tiết (bao gồm cả tiết GVCN nếu có), vượt quá giới hạn cho phép là {$maxSlots} tiết/tuần!"
                ];
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kiểm tra không được xếp lịch đè lên các tiết học cố định như Chào cờ hoặc Sinh hoạt lớp.
     * 
     * @param int $d Thứ.
     * @param int $p Tiết.
     * @param int $flagDay Thứ chào cờ.
     * @param int $flagPeriod Tiết chào cờ.
     * @param int $meetDay Thứ sinh hoạt lớp.
     * @param int $meetPeriod Tiết sinh hoạt lớp.
     * @return array|null Trả về mảng chứa thông báo lỗi nếu vi phạm, ngược lại trả về null.
     */
    private function validateFixedPeriods(int $d, int $p, $flagDay, $flagPeriod, $meetDay, $meetPeriod): ?array
    {
        if (($d == $flagDay && $p == $flagPeriod) || ($d == $meetDay && $p == $meetPeriod)) {
            return ['message' => 'Hệ thống từ chối: Không được phép xếp môn đè lên Chào cờ hoặc Sinh hoạt lớp!'];
        }
        return null;
    }

    /**
     * Kiểm tra xem tiết học có rơi vào ngày nghỉ đăng ký của giáo viên hay không.
     * 
     * @param mixed $assignment Đối tượng phân công (có quan hệ teacher).
     * @param int $d Thứ trong tuần.
     * @return array|null
     */
    private function validateTeacherOffDay($assignment, int $d): ?array
    {
        $offDays = is_array($assignment->teacher->off_days)
            ? $assignment->teacher->off_days
            : json_decode($assignment->teacher->off_days ?? '[]', true);

        if ($assignment->teacher && in_array($d, $offDays)) {
            return ['message' => "Lịch nghỉ: Giáo viên {$assignment->teacher->name} đã xin nghỉ vào Thứ {$d}!"];
        }
        return null;
    }

    /**
     * Kiểm tra trùng lịch dạy của giáo viên với các lớp học khác tại cùng một thời điểm.
     * 
     * @param int $teacherId ID giáo viên.
     * @param string $slotKey Mã slot (Thứ-Tiết).
     * @param mixed $assignment Đối tượng phân công.
     * @param int $d Thứ.
     * @param int $p Tiết.
     * @param array $teacherBusySlots Danh sách các slot bận của giáo viên đã load sẵn.
     * @return array|null
     */
    private function validateTeacherConflictMemory(int $teacherId, string $slotKey, $assignment, int $d, int $p, array $teacherBusySlots): ?array
    {
        $busySlots = $teacherBusySlots[$teacherId] ?? [];
        if (in_array($slotKey, $busySlots)) {
            $name = $assignment->teacher->name ?? 'N/A';
            return [
                'message' => "Trùng lịch: GV {$name} đang bận dạy lớp khác vào Thứ {$d} - Tiết {$p}!"
            ];
        }
        return null;
    }

    /**
     * Kiểm tra trùng phòng học với các lớp học khác tại cùng một thời điểm.
     * 
     * @param int $roomId ID phòng học.
     * @param string $slotKey Mã slot (Thứ-Tiết).
     * @param int $d Thứ.
     * @param int $p Tiết.
     * @param array $roomBusySlots Danh sách các slot bận của phòng học đã load sẵn.
     * @param \Illuminate\Support\Collection $allRooms Danh sách các phòng học.
     * @return array|null
     */
    private function validateRoomConflictMemory($roomId, string $slotKey, int $d, int $p, array $roomBusySlots, $allRooms): ?array
    {
        $busySlots = $roomBusySlots[$roomId] ?? [];
        if (in_array($slotKey, $busySlots)) {
            $roomName = $allRooms[$roomId]->name ?? "Phòng ID {$roomId}";
            return [
                'message' => "Trùng phòng học: {$roomName} đang được lớp khác sử dụng vào Thứ {$d} - Tiết {$p}!"
            ];
        }
        return null;
    }

    /**
     * Kiểm tra xem số lượng tiết học của từng môn trong tuần có vượt quá định mức (curriculum) hay không.
     * 
     * @param array $assignmentCounts Số tiết đã xếp cho từng phân công.
     * @param mixed $allAssignments Danh sách phân công.
     * @param Classroom $classroom Đối tượng lớp học.
     * @param array $settings Cài đặt hệ thống.
     * @param array $curriculums Định mức tiết học load sẵn.
     * @return array|null
     */
    private function validateSlotLimits(array $assignmentCounts, $allAssignments, Classroom $classroom, array $settings, array $curriculums = []): ?array
    {
        $blockName = $classroom->block_name;

        // Nếu chưa truyền vào thì mới query (fallback cho backward compatibility)
        $configs = !empty($curriculums) ? $curriculums : SubjectConfiguration::where('grade', $classroom->grade)
            ->where('block', $blockName)
            ->pluck('slots_per_week', 'subject_id')
            ->all();

        foreach ($assignmentCounts as $asId => $count) {
            $asmt = $allAssignments[$asId] ?? null;
            if (!$asmt) continue;

            $maxSlots = $configs[$asmt->subject_id] ?? null;
            if (!$maxSlots) continue;

            if ($count > $maxSlots) {
                $subName = $asmt->subject->name ?? 'Môn học';
                return [
                    'message' => "Vượt định mức: Môn {$subName} chỉ được phép xếp tối đa {$maxSlots} tiết/tuần cho khối {$classroom->grade}!"
                ];
            }
        }
        return null;
    }

    /**
     * Kiểm tra xem giáo viên có vượt quá số ngày dạy tối đa trong tuần theo cấu hình không.
     * 
     * @param int $tId ID giáo viên.
     * @param array $days Danh sách các ngày dạy trong lớp hiện tại.
     * @param mixed $allAssignments Danh sách phân công.
     * @param int $maxDaysPerWeek Số ngày tối đa cho phép.
     * @param array $teacherOtherDays Danh sách các ngày giáo viên đã dạy ở lớp khác.
     * @return array|null
     */
    private function validateMaxDaysMemory(int $tId, array $days, $allAssignments, int $maxDaysPerWeek, array $teacherOtherDays): ?array
    {
        $daysThisClass    = array_keys($days);
        $daysOtherClasses = $teacherOtherDays[$tId] ?? [];

        $totalUniqueDays = count(array_unique(array_merge($daysThisClass, $daysOtherClasses)));
        if ($totalUniqueDays > $maxDaysPerWeek) {
            $teacher = $allAssignments->first(fn($a) => $a->teacher_id == $tId)?->teacher;
            $name = $teacher->name ?? 'N/A';
            return [
                'message' => "Giới hạn hệ thống: GV {$name} vượt quá số ngày dạy tối đa trong tuần (Đang xếp: {$totalUniqueDays} ngày, Cho phép: {$maxDaysPerWeek} ngày)."
            ];
        }
        return null;
    }

    /**
     * Kiểm tra giáo viên không được dạy quá số tiết liên tiếp tối đa cho phép trong một buổi.
     * 
     * @param int $tId ID giáo viên.
     * @param array $days Danh sách các tiết theo ngày.
     * @param mixed $allAssignments Danh sách phân công.
     * @param int $maxConsecutive Số tiết liên tiếp tối đa.
     * @return array|null
     */
    private function validateConsecutiveSlots(int $tId, array $days, $allAssignments, int $maxConsecutive): ?array
    {
        foreach ($days as $day => $periods) {
            sort($periods);
            $consecutive = 1;
            $maxFound    = 1;
            for ($i = 1; $i < count($periods); $i++) {
                if ($periods[$i] === $periods[$i - 1] + 1) {
                    $consecutive++;
                    $maxFound = max($maxFound, $consecutive);
                } else {
                    $consecutive = 1;
                }
            }
            if ($maxFound > $maxConsecutive) {
                $teacher = $allAssignments->first(fn($a) => $a->teacher_id == $tId)?->teacher;
                $name = $teacher->name ?? 'N/A';
                return [
                    'message' => "Vi phạm cấu hình: GV {$name} dạy liên tiếp {$maxFound} tiết vào Thứ {$day} (Giới hạn hệ thống là {$maxConsecutive} tiết)!"
                ];
            }
        }
        return null;
    }

    /**
     * Kiểm tra xem giáo viên hoặc lớp học có bị tình trạng "tiết trống" xen kẽ giữa các tiết học trong một buổi không.
     * Đảm bảo các tiết học được xếp liên tục nhau.
     * 
     * @param mixed $id ID của đối tượng (giáo viên hoặc lớp).
     * @param array $days Danh sách tiết học theo ngày.
     * @param mixed|null $allAssignments Danh sách phân công (nếu kiểm tra cho giáo viên).
     * @param string $type Loại đối tượng ('giáo viên' hoặc 'lớp học').
     * @return array|null
     */
    private function validateGapSession($id, array $days, $allAssignments, string $type): ?array
    {
        foreach ($days as $day => $periods) {
            if (count($periods) < 2) continue;
            sort($periods);

            $morning = array_filter($periods, fn($p) => $p <= 5);
            $afternoon = array_filter($periods, fn($p) => $p > 5);

            foreach ([$morning, $afternoon] as $sessionPeriods) {
                if (count($sessionPeriods) < 2) continue;
                
                $min = min($sessionPeriods);
                $max = max($sessionPeriods);
                
                // Nếu số lượng tiết ít hơn khoảng cách min-max => có lỗ hổng
                if (count($sessionPeriods) < ($max - $min + 1)) {
                    $entityName = ($type === 'giáo viên' && $allAssignments) 
                        ? "GV " . ($allAssignments->first(fn($a) => $a->teacher_id == $id)?->teacher->name ?? 'N/A')
                        : "Lớp học";

                    return [
                        'message' => "Cảnh báo Tiết trống: {$entityName} đang có tiết học bị cách quãng (Ví dụ: Dạy tiết 1 và 3 nhưng trống tiết 2) vào Thứ {$day}. Vui lòng sắp xếp các tiết học liên tục nhau!"
                    ];
                }
            }
        }
        return null;
    }
}
