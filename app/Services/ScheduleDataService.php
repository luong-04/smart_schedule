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
     * Lấy các slot đã bận của giáo viên và phòng học từ lịch các lớp khác.
     */
    public function getBusySlots(string $scheduleName, int $selectedClassId): array
    {
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

        return [$teacherBusySlots, $teacherOtherDays, $roomBusySlots];
    }

    /**
     * Lấy số lượng tiết đã sử dụng cho giáo viên và các môn học.
     */
    public function getUsedCounts(string $scheduleName, Collection $allAssignments): array
    {
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

        return [$teacherUsedCounts, $assignmentUsedCounts];
    }

    /**
     * Xây dựng danh sách các môn học hợp lệ kèm theo kiểm tra số tiết.
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

            if ($gvcnClassCount > 0) {
                if ($assignFlag)   $teacherUsed += 1;
                if ($assignMeeting) $teacherUsed += 1;
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
