<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\SubjectConfiguration;
use App\Models\Setting;
use App\Models\Room;

/**
 * Seeder nạp dữ liệu Thời khóa biểu mẫu (Sample Schedules).
 * Tự động xếp lịch dựa trên các phân công đã có, tuân thủ các quy tắc cơ bản.
 */
class SampleScheduleSeeder extends Seeder
{
    /**
     * Chạy tiến trình nạp TKB mẫu.
     */
    public function run()
    {
        $scheduleName = $this->getScheduleName();
        $appliesFrom = now()->startOfWeek()->toDateString();
        $appliesTo = now()->endOfYear()->toDateString();

        // 1. Xóa lịch cũ cùng tên để nạp lại mới hoàn toàn
        Schedule::where('schedule_name', $scheduleName)->delete();

        $classrooms = Classroom::all()->shuffle();
        $theoryRooms = Room::whereHas('roomType', function($q) { $q->where('name', 'Phòng học lý thuyết'); })->get();
        $specialRooms = Room::whereDoesntHave('roomType', function($q) { $q->where('name', 'Phòng học lý thuyết'); })->get();

        $usedTeacherSlots = []; 
        $usedRoomSlots = [];    

        // Lấy cấu hình các tiết cố định (Chào cờ, Sinh hoạt)
        $settings = [
            'morning' => [
                'f_day' => (int)Setting::getVal('morning_flag_day', 2),
                'f_per' => (int)Setting::getVal('morning_flag_period', 1),
                'm_day' => (int)Setting::getVal('morning_meeting_day', 7),
                'm_per' => (int)Setting::getVal('morning_meeting_period', 5),
            ],
            'afternoon' => [
                'f_day' => (int)Setting::getVal('afternoon_flag_day', 2),
                'f_per' => (int)Setting::getVal('afternoon_flag_period', 6),
                'm_day' => (int)Setting::getVal('afternoon_meeting_day', 7),
                'm_per' => (int)Setting::getVal('afternoon_meeting_period', 10),
            ]
        ];

        foreach ($classrooms as $cls) {
            $assignments = Assignment::with(['subject', 'teacher'])->where('class_id', $cls->id)->get();
            $shift = $cls->shift;
            $usedClassSlots = [];

            $s = $settings[$shift];
            $fixedSlots = ["{$s['f_day']}-{$s['f_per']}", "{$s['m_day']}-{$s['m_per']}"];

            $curriculums = SubjectConfiguration::where('grade', $cls->grade)
                ->where('block', $cls->block)
                ->pluck('slots_per_week', 'subject_id')
                ->all();

            $totalAcademicSlots = array_sum($curriculums);
            
            // Phân bổ số tiết học cho từng ngày trong tuần (Thứ 2 - Thứ 7)
            $slotsPerDay = [2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0];
            $tempTotal = $totalAcademicSlots;
            
            // Ưu tiên nạp đầy Thứ 2 và Thứ 7 (thường có 4 tiết học + 1 tiết cố định)
            foreach ([2, 7] as $d) {
                $target = min($tempTotal, 4);
                $slotsPerDay[$d] = $target;
                $tempTotal -= $target;
            }

            // Phân bổ cho các ngày còn lại (Thứ 3 - Thứ 6)
            foreach ([3, 4, 5, 6] as $d) {
                $target = min($tempTotal, 5);
                $slotsPerDay[$d] = $target;
                $tempTotal -= $target;
            }

            // Tạo danh sách các slot khả dụng (loại trừ Tiết cố định)
            $eligibleSlots = [];
            foreach ($slotsPerDay as $d => $count) {
                $start = ($shift === 'morning') ? 1 : 6;
                $placed = 0; $p = $start;
                while ($placed < $count && $p < $start + 5) {
                    $slotKey = "$d-$p";
                    if (!in_array($slotKey, $fixedSlots)) {
                        $eligibleSlots[] = ['d' => $d, 'p' => $p];
                        $placed++;
                    }
                    $p++;
                }
            }

            // Tách riêng các môn đặc thù (Thể chất, QP-AN) - Thường học khác buổi
            $oppositeSubjects = $assignments->filter(fn($as) => in_array($as->subject->name, ['Giáo dục thể chất', 'Giáo dục quốc phòng và an ninh']));
            $generalSubjects  = $assignments->filter(fn($as) => !in_array($as->subject->name, ['Giáo dục thể chất', 'Giáo dục quốc phòng và an ninh']));

            // 1. Xếp các môn trái buổi
            $oppShift = ($shift === 'morning') ? 'afternoon' : 'morning';
            $os = $settings[$oppShift];
            $oppFixed = ["{$os['f_day']}-{$os['f_per']}", "{$os['m_day']}-{$os['m_per']}"];
            $oppositeShiftPeriods = ($shift === 'morning') ? range(6, 10) : range(1, 5);

            foreach ($oppositeSubjects as $as) {
                $slotsNeeded = $curriculums[$as->subject_id] ?? 0;
                $this->scheduleAssignment($as, $slotsNeeded, $oppositeShiftPeriods, $scheduleName, $appliesFrom, $appliesTo, $usedTeacherSlots, $usedRoomSlots, $usedClassSlots, $specialRooms, $oppFixed);
            }

            // 2. Xếp các môn học thông thường (Đảm bảo không có "tiết trống" giữa các buổi)
            $generalSubjectList = [];
            foreach ($generalSubjects as $as) {
                $count = $curriculums[$as->subject_id] ?? 0;
                for ($i = 0; $i < $count; $i++) { $generalSubjectList[] = $as; }
            }
            shuffle($generalSubjectList);

            foreach ($generalSubjectList as $as) {
                foreach ($eligibleSlots as $idx => $slot) {
                    $d = $slot['d']; $p = $slot['p']; $slotKey = "$d-$p";

                    if (!in_array($slotKey, $usedClassSlots) && $this->isSlotAvailable($as, $slotKey, $usedTeacherSlots, $usedRoomSlots, $specialRooms, $roomFoundId)) {
                        Schedule::create([
                            'schedule_name' => $scheduleName,
                            'applies_from'  => $appliesFrom,
                            'applies_to'    => $appliesTo,
                            'assignment_id' => $as->id,
                            'class_id'      => $as->class_id,
                            'teacher_id'    => $as->teacher_id,
                            'room_id'       => $roomFoundId,
                            'day_of_week'   => $d,
                            'period'        => $p
                        ]);
                        $usedClassSlots[] = $slotKey;
                        $usedTeacherSlots[$as->teacher_id][] = $slotKey;
                        if ($roomFoundId) $usedRoomSlots[$roomFoundId][] = $slotKey;
                        unset($eligibleSlots[$idx]);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Kiểm tra tính khả dụng của một Slot (Trùng lịch GV, Trùng Phòng).
     */
    private function isSlotAvailable($as, $slotKey, $usedTeacherSlots, $usedRoomSlots, $specialRooms, &$roomFoundId)
    {
        if (isset($usedTeacherSlots[$as->teacher_id]) && in_array($slotKey, $usedTeacherSlots[$as->teacher_id])) return false;

        $roomFoundId = null;
        if ($as->subject->type !== 'theory') {
            foreach ($specialRooms as $room) {
                if ($room->room_type_id == $as->subject->room_type_id) {
                    if (!isset($usedRoomSlots[$room->id]) || !in_array($slotKey, $usedRoomSlots[$room->id])) {
                        $roomFoundId = $room->id;
                        break;
                    }
                }
            }
            if (!$roomFoundId) return false;
        }
        return true;
    }

    /**
     * Helper thực hiện xếp lịch cho một phân công.
     */
    private function scheduleAssignment($as, $slotsNeeded, $allowedPeriods, $scheduleName, $appliesFrom, $appliesTo, &$usedTeacherSlots, &$usedRoomSlots, &$usedClassSlots, $specialRooms, $excludeSlots = [])
    {
        $slotsPlaced = 0;
        $days = [2, 3, 4, 5, 6, 7];
        shuffle($days);
        $possibleSlots = [];
        foreach ($days as $d) { 
            foreach ($allowedPeriods as $p) { 
                $slotKey = "$d-$p";
                if (!in_array($slotKey, $excludeSlots)) $possibleSlots[] = ['d' => $d, 'p' => $p]; 
            } 
        }
        shuffle($possibleSlots);

        while ($slotsPlaced < $slotsNeeded && !empty($possibleSlots)) {
            $slot = array_shift($possibleSlots);
            $slotKey = "{$slot['d']}-{$slot['p']}";
            if (in_array($slotKey, $usedClassSlots)) continue;
            
            if ($this->isSlotAvailable($as, $slotKey, $usedTeacherSlots, $usedRoomSlots, $specialRooms, $roomFoundId)) {
                Schedule::create([
                    'schedule_name' => $scheduleName,
                    'applies_from'  => $appliesFrom,
                    'applies_to'    => $appliesTo,
                    'assignment_id' => $as->id,
                    'class_id'      => $as->class_id,
                    'teacher_id'    => $as->teacher_id,
                    'room_id'       => $roomFoundId,
                    'day_of_week'   => $slot['d'],
                    'period'        => $slot['p']
                ]);
                $usedTeacherSlots[$as->teacher_id][] = $slotKey;
                if ($roomFoundId) $usedRoomSlots[$roomFoundId][] = $slotKey;
                $usedClassSlots[] = $slotKey;
                $slotsPlaced++;
            }
        }
    }

    /**
     * Lấy tên bản thời khóa biểu dựa trên cài đặt hệ thống.
     */
    private function getScheduleName(): string
    {
        $semester   = Setting::getVal('semester', 'Học kỳ 1');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        return $semester . ' - ' . $schoolYear;
    }
}
