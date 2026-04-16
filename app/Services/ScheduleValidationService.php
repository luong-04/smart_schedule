<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\SubjectConfiguration;
use App\Models\Room;

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
     * @param array     $allAssignments  Collection assignment đã load sẵn (keyBy id)
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
        string $shiftStr
    ): ?array {
        // Tính toán các tiết cố định từ settings
        $isMorning  = ($shiftStr === 'morning');
        $flagDay    = $settings[$shiftStr . '_flag_day']    ?? \App\Models\Setting::DEFAULT_FLAG_DAY;
        $flagPeriod = $settings[$shiftStr . '_flag_period'] ?? ($isMorning ? \App\Models\Setting::DEFAULT_FLAG_PER_M : \App\Models\Setting::DEFAULT_FLAG_PER_A);
        $meetDay    = $settings[$shiftStr . '_meeting_day']    ?? \App\Models\Setting::DEFAULT_MEET_DAY;
        $meetPeriod = $settings[$shiftStr . '_meeting_period'] ?? ($isMorning ? \App\Models\Setting::DEFAULT_MEET_PER_M : \App\Models\Setting::DEFAULT_MEET_PER_A);

        $maxConsecutive      = (int) ($settings['max_consecutive_slots'] ?? 3);
        $maxDaysPerWeek      = (int) ($settings['max_days_per_week']     ?? 6);
        $checkTeacherConflict = (bool) ($settings['check_teacher_conflict'] ?? false);
        $checkRoomConflict    = (bool) ($settings['check_room_conflict']    ?? false);

        // Map để gom lịch theo giáo viên trong request này: [teacherId => [day => [periods]]]
        $teacherDayPeriods = [];
        // Map đếm số tiết theo assignment trong request: [assignmentId => count]
        $assignmentCounts = [];

        // Load rooms một lần dùng cho check loại phòng
        $allRooms = $this->room->all()->keyBy('id');

        foreach ($schedules as $item) {
            $assignment = $allAssignments[$item['assignment_id']] ?? null;
            if (!$assignment) {
                return ['message' => 'Phân công không tồn tại trong hệ thống!'];
            }

            $teacherId = $assignment->teacher_id;
            $d         = (int) $item['day_of_week'];
            $p         = (int) $item['period'];
            $roomId    = $item['room_id'] ?? null;

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
                if ($error = $this->validateTeacherConflict($scheduleName, $classroom->id, $teacherId, $d, $p, $assignment)) {
                    return $error;
                }
            }

            // ── 4. Kiểm tra trùng phòng học ───────────────────────────────────
            if ($roomId) {
                if ($checkRoomConflict) {
                    if ($error = $this->validateRoomConflict($scheduleName, $classroom->id, $roomId, $d, $p)) {
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
        if ($error = $this->validateSlotLimits($assignmentCounts, $allAssignments, $classroom, $settings)) {
            return $error;
        }

        // ── 6. Kiểm tra tiết liên tiếp + số ngày dạy tối đa ─────────────────
        foreach ($teacherDayPeriods as $tId => $days) {
            if ($error = $this->validateMaxDays($scheduleName, $classroom->id, $tId, $days, $allAssignments, $maxDaysPerWeek)) {
                return $error;
            }
            if ($error = $this->validateConsecutiveSlots($tId, $days, $allAssignments, $maxConsecutive)) {
                return $error;
            }
        }

        return null; // Mọi kiểm tra đều pass
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kiểm tra không được xếp lịch đè lên tiết Chào cờ hoặc Sinh hoạt lớp
     */
    private function validateFixedPeriods(int $d, int $p, $flagDay, $flagPeriod, $meetDay, $meetPeriod): ?array
    {
        if (($d == $flagDay && $p == $flagPeriod) || ($d == $meetDay && $p == $meetPeriod)) {
            return ['message' => 'Hệ thống từ chối: Không được phép xếp môn đè lên Chào cờ hoặc Sinh hoạt lớp!'];
        }
        return null;
    }

    /**
     * Kiểm tra ngày nghỉ đăng ký của giáo viên
     */
    private function validateTeacherOffDay($assignment, int $d): ?array
    {
        $offDays = is_array($assignment->teacher->off_days)
            ? $assignment->teacher->off_days
            : json_decode($assignment->teacher->off_days ?? '[]', true);

        if (in_array($d, $offDays)) {
            return ['message' => "Lịch nghỉ: Giáo viên {$assignment->teacher->name} đã xin nghỉ vào Thứ {$d}!"];
        }
        return null;
    }

    /**
     * Kiểm tra giáo viên bị trùng lịch với lớp khác (Sử dụng DB query mượt mà hơn cho array lớn)
     */
    private function validateTeacherConflict(string $scheduleName, int $classId, int $teacherId, int $d, int $p, $assignment): ?array
    {
        $conflict = Schedule::where('schedule_name', $scheduleName)
            ->where('day_of_week', $d)
            ->where('period', $p)
            ->whereHas('assignment', function ($q) use ($teacherId, $classId) {
                $q->where('teacher_id', $teacherId)
                  ->where('class_id', '!=', $classId);
            })
            ->with('assignment.classroom')
            ->first();

        if ($conflict) {
            return [
                'message' => "Trùng lịch: GV {$assignment->teacher->name} đang dạy lớp {$conflict->assignment->classroom->name} vào Thứ {$d} - Tiết {$p}!"
            ];
        }
        return null;
    }

    /**
     * Kiểm tra phòng học bị trùng với lớp khác (Sử dụng DB query)
     */
    private function validateRoomConflict(string $scheduleName, int $classId, $roomId, int $d, int $p): ?array
    {
        $roomConflict = Schedule::where('schedule_name', $scheduleName)
            ->where('day_of_week', $d)
            ->where('period', $p)
            ->where('room_id', $roomId)
            ->whereHas('assignment', function ($q) use ($classId) {
                $q->where('class_id', '!=', $classId);
            })
            ->with(['assignment.classroom', 'room'])
            ->first();

        if ($roomConflict) {
            return [
                'message' => "Trùng phòng học: {$roomConflict->room->name} đang được lớp {$roomConflict->assignment->classroom->name} sử dụng vào Thứ {$d} - Tiết {$p}!"
            ];
        }
        return null;
    }

    /**
     * Kiểm tra không vượt quá định mức tiết/tuần của môn học theo lớp
     */
    private function validateSlotLimits(array $assignmentCounts, $allAssignments, Classroom $classroom, array $settings): ?array
    {
        $blockName = $classroom->block_name;

        $configs = SubjectConfiguration::where('grade', $classroom->grade)
            ->where('block', $blockName)
            ->pluck('slots_per_week', 'subject_id')
            ->all();

        foreach ($assignmentCounts as $asId => $count) {
            $asmt = $allAssignments[$asId] ?? null;
            if (!$asmt) continue;

            $maxSlots = $configs[$asmt->subject_id] ?? null;
            if (!$maxSlots) continue;

            if ($count > $maxSlots) {
                return [
                    'message' => "Vượt định mức: Môn {$asmt->subject->name} chỉ được phép xếp tối đa {$maxSlots} tiết/tuần cho khối {$classroom->grade}!"
                ];
            }
        }
        return null;
    }

    /**
     * Kiểm tra số ngày dạy tối đa trong tuần của giáo viên (Sử dụng DB query hiệu năng cao)
     */
    private function validateMaxDays(string $scheduleName, int $classId, int $tId, array $days, $allAssignments, int $maxDaysPerWeek): ?array
    {
        $daysThisClass    = array_keys($days);
        
        $daysOtherClasses = Schedule::where('schedule_name', $scheduleName)
            ->whereHas('assignment', function ($q) use ($tId, $classId) {
                $q->where('teacher_id', $tId)
                  ->where('class_id', '!=', $classId);
            })
            ->pluck('day_of_week')
            ->unique()
            ->toArray();

        $totalUniqueDays = count(array_unique(array_merge($daysThisClass, $daysOtherClasses)));
        if ($totalUniqueDays > $maxDaysPerWeek) {
            $teacher = $allAssignments->first(fn($a) => $a->teacher_id == $tId)?->teacher;
            return [
                'message' => "Giới hạn hệ thống: GV {$teacher->name} vượt quá số ngày dạy tối đa trong tuần (Đang xếp: {$totalUniqueDays} ngày, Cho phép: {$maxDaysPerWeek} ngày)."
            ];
        }
        return null;
    }

    /**
     * Kiểm tra giáo viên không dạy quá số tiết liên tiếp cho phép
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
                return [
                    'message' => "Vi phạm cấu hình: GV {$teacher->name} dạy liên tiếp {$maxFound} tiết vào Thứ {$day} (Giới hạn hệ thống là {$maxConsecutive} tiết)!"
                ];
            }
        }
        return null;
    }
}
