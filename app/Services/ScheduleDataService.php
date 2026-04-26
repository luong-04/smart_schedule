<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Classroom;
use Illuminate\Support\Collection;

/**
 * Service Layer để xử lý việc tính toán dữ liệu cho màn hình Xếp Lịch.
 */
class ScheduleDataService
{
    /**
     * Lấy các slot đã bận của giáo viên và phòng học từ lịch các lớp khác trong cùng một bản thời khóa biểu và ngày áp dụng.
     * 
     * @param string $scheduleName Tên bản thời khóa biểu.
     * @param int $selectedClassId ID lớp học đang xếp (để loại trừ).
     * @param string $appliesFrom Ngày áp dụng.
     * @return array Mảng chứa các slot bận của giáo viên và phòng học.
     */
    public function getBusySlots(string $scheduleName, int $selectedClassId, string $appliesFrom): array
    {
        $otherSchedules = Schedule::where('schedule_name', $scheduleName)
            ->where('class_id', '!=', $selectedClassId)
            ->where('applies_from', $appliesFrom)
            ->with(['assignment.classroom']) // Load thêm quan hệ để lấy tên lớp học
            ->get(['id', 'assignment_id', 'room_id', 'teacher_id', 'day_of_week', 'period', 'class_id']);

        $teacherBusySlots = [];
        $teacherOtherDays = [];
        $roomBusySlots    = [];

        foreach ($otherSchedules as $sch) {
            $tId = $sch->teacher_id;
            if (!$tId) continue;

            $rId = $sch->room_id;
            $slotKey = $sch->day_of_week . '-' . $sch->period;
            $className = $sch->assignment->classroom->name ?? ('Lớp ID ' . $sch->class_id);

            // Lưu thông tin slot bận của giáo viên kèm tên lớp học
            $teacherBusySlots[$tId][$slotKey] = $className;

            // Thu thập các ngày trong tuần giáo viên đã có lịch dạy ở lớp khác
            if (!isset($teacherOtherDays[$tId])) $teacherOtherDays[$tId] = [];
            if (!in_array($sch->day_of_week, $teacherOtherDays[$tId])) {
                $teacherOtherDays[$tId][] = $sch->day_of_week;
            }

            // Lưu thông tin slot bận của phòng học kèm tên lớp học
            if ($rId) {
                $roomBusySlots[$rId][$slotKey] = $className;
            }
        }

        return [$teacherBusySlots, $teacherOtherDays, $roomBusySlots];
    }

    /**
     * Lấy số lượng tiết đã sử dụng của giáo viên và các môn học trong bản thời khóa biểu cụ thể.
     * 
     * @param string $scheduleName Tên bản thời khóa biểu.
     * @param Collection $allAssignments Danh sách các phân công giảng dạy.
     * @param string $appliesFrom Ngày áp dụng.
     * @return array [teacherUsedCounts, assignmentUsedCounts]
     */
    public function getUsedCounts(string $scheduleName, Collection $allAssignments, string $appliesFrom): array
    {
        // Tối ưu: Dùng trực tiếp teacher_id đã denormalize ở bảng schedules
        $teacherUsedCounts = Schedule::where('schedule_name', $scheduleName)
            ->where('applies_from', $appliesFrom)
            ->selectRaw('teacher_id, COUNT(*) as total')
            ->groupBy('teacher_id')
            ->pluck('total', 'teacher_id')
            ->all();

        $assignmentUsedCounts = Schedule::where('schedule_name', $scheduleName)
            ->where('applies_from', $appliesFrom)
            ->whereIn('assignment_id', $allAssignments->pluck('id'))
            ->selectRaw('assignment_id, COUNT(*) as total')
            ->groupBy('assignment_id')
            ->pluck('total', 'assignment_id')
            ->all();

        return [$teacherUsedCounts, $assignmentUsedCounts];
    }

    /**
     * Xây dựng danh sách các phân công giảng dạy hợp lệ, tính toán số tiết còn lại dựa trên định mức và các ràng buộc.
     * 
     * @param Collection $allAssignments Danh sách phân công.
     * @param array $curriculums Định mức tiết học của từng môn.
     * @param array $counts Số tiết đã sử dụng.
     * @param array $settings Cài đặt hệ thống.
     * @return Collection Danh sách phân công đã được tính toán số tiết còn lại.
     */
    public function buildValidAssignments(Collection $allAssignments, array $curriculums, array $counts, array $settings): Collection
    {
        [$teacherUsedCounts, $assignmentUsedCounts] = $counts;

        $assignFlag    = $settings['assign_gvcn_flag_salute']    ?? 0;
        $assignMeeting = $settings['assign_gvcn_class_meeting']  ?? 0;

        // Đếm lớp GVCN theo ID thay vì Name (tối ưu Database)
        $teacherIds = $allAssignments->pluck('teacher_id')->unique();
        $gvcnCounts = Classroom::whereIn('homeroom_teacher_id', $teacherIds)
            ->selectRaw('homeroom_teacher_id, COUNT(*) as cnt')
            ->groupBy('homeroom_teacher_id')
            ->pluck('cnt', 'homeroom_teacher_id')
            ->all();

        $validAssignments = collect();
        foreach ($allAssignments as $as) {
            if (!isset($curriculums[$as->subject_id])) continue;

            $maxSubjectSlots = $curriculums[$as->subject_id];
            $teacherUsed     = $teacherUsedCounts[$as->teacher_id] ?? 0;
            $gvcnClassCount  = $gvcnCounts[$as->teacher_id] ?? 0;

            // Tính tiết định mức cho GVCN dựa trên Cài đặt (Chào cờ/Sinh hoạt)
            if ($gvcnClassCount > 0) {
                if ($assignFlag)    $teacherUsed += $gvcnClassCount;
                if ($assignMeeting) $teacherUsed += $gvcnClassCount;
            }

            $as->teacher_remaining       = max(0, $as->teacher->max_slots_week - $teacherUsed);
            $subjectUsed                 = $assignmentUsedCounts[$as->id] ?? 0;
            $as->remaining_subject_slots = max(0, $maxSubjectSlots - $subjectUsed);
            $as->actual_remaining        = min($as->teacher_remaining, $as->remaining_subject_slots);

            // Xác định "điểm nghẽn" để hiển thị UI minh bạch
            if ($as->actual_remaining === 0) {
                if ($as->teacher_remaining <= 0 && $as->remaining_subject_slots > 0) {
                    $as->bottleneck = 'teacher';
                } elseif ($as->remaining_subject_slots <= 0 && $as->teacher_remaining > 0) {
                    $as->bottleneck = 'subject';
                } else {
                    $as->bottleneck = 'both';
                }
            } else {
                $as->bottleneck = 'none';
            }

            $validAssignments->push($as);
        }

        return $validAssignments;
    }
}
