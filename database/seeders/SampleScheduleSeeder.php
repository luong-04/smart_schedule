<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\SubjectConfiguration;
use App\Models\Setting;
use App\Models\Room;

class SampleScheduleSeeder extends Seeder
{
    public function run()
    {
        $scheduleName = $this->getScheduleName();
        $appliesFrom = now()->startOfWeek()->toDateString();
        $appliesTo = now()->endOfYear()->toDateString();

        // 1. Clear existing schedules for this name to start fresh
        Schedule::where('schedule_name', $scheduleName)->delete();

        $classrooms = Classroom::all()->shuffle();
        $theoryRooms = Room::whereHas('roomType', function($q) { $q->where('name', 'Phòng học lý thuyết'); })->get();
        $specialRooms = Room::whereDoesntHave('roomType', function($q) { $q->where('name', 'Phòng học lý thuyết'); })->get();

        $usedTeacherSlots = []; 
        $usedRoomSlots = [];    

        // Fetch Fixed slots settings
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
            
            // Distribute slots across 6 days (Mon-Sat)
            // Rule: D2 and D7 should have 4 academic slots + 1 fixed = 5 total
            $slotsPerDay = [2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0];
            $tempTotal = $totalAcademicSlots;
            
            // Priority 1: Set D2 and D7 to 4 academic slots (if total allows)
            foreach ([2, 7] as $d) {
                $target = min($tempTotal, 4);
                $slotsPerDay[$d] = $target;
                $tempTotal -= $target;
            }

            // Priority 2: Fill D3, D4, D5, D6 to 5 academic slots
            foreach ([3, 4, 5, 6] as $d) {
                $target = min($tempTotal, 5);
                $slotsPerDay[$d] = $target;
                $tempTotal -= $target;
            }

            // Priority 3: If still has extra (very rare), add to D2/D7 but careful of 5 total cap
            // and then to others if they can take more (unlikely in one shift)
            $allDays = [2, 3, 4, 5, 6, 7];
            while ($tempTotal > 0) {
                $changed = false;
                foreach ($allDays as $d) {
                    $limit = (in_array($d, [2, 7])) ? 4 : 5; // Academic limit per day
                    if ($tempTotal > 0 && $slotsPerDay[$d] < $limit) {
                        $slotsPerDay[$d]++;
                        $tempTotal--;
                        $changed = true;
                    }
                }
                if (!$changed) break; // Avoid infinite loop if curriculum > capacity
            }

            // Create eligible slots (Continuity check & Fixed slot exclusion)
            $eligibleSlots = [];
            foreach ($slotsPerDay as $d => $count) {
                $start = ($shift === 'morning') ? 1 : 6;
                $placed = 0;
                $p = $start;
                // We need to place $count academic slots, skipping the fixed one if it falls in range
                while ($placed < $count && $p < $start + 6) {
                    $slotKey = "$d-$p";
                    if (!in_array($slotKey, $fixedSlots)) {
                        $eligibleSlots[] = ['d' => $d, 'p' => $p];
                        $placed++;
                    }
                    $p++;
                }
            }

            // Separate assignments
            $oppositeSubjects = $assignments->filter(fn($as) => in_array($as->subject->name, ['Giáo dục thể chất', 'Giáo dục quốc phòng và an ninh']));
            $generalSubjects  = $assignments->filter(fn($as) => !in_array($as->subject->name, ['Giáo dục thể chất', 'Giáo dục quốc phòng và an ninh']));

            // 1. Schedule Opposite Shift Subjects (PE/Defense) - These can be anywhere in opposite shift
            $oppShift = ($shift === 'morning') ? 'afternoon' : 'morning';
            $os = $settings[$oppShift];
            $oppFixed = ["{$os['f_day']}-{$os['f_per']}", "{$os['m_day']}-{$os['m_per']}"];
            $oppositeShiftPeriods = ($shift === 'morning') ? range(6, 10) : range(1, 5);

            foreach ($oppositeSubjects as $as) {
                $slotsNeeded = $curriculums[$as->subject_id] ?? 0;
                $this->scheduleAssignment($as, $slotsNeeded, $oppositeShiftPeriods, $scheduleName, $appliesFrom, $appliesTo, $usedTeacherSlots, $usedRoomSlots, $usedClassSlots, $specialRooms, $oppFixed);
            }

            // 2. Schedule General Subjects in the gap-free eligible slots
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
                            'room_id'       => $roomFoundId, // Will be null for theory
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

    private function isSlotAvailable($as, $slotKey, $usedTeacherSlots, $usedRoomSlots, $specialRooms, &$roomFoundId)
    {
        // Teacher conflict check
        if (isset($usedTeacherSlots[$as->teacher_id]) && in_array($slotKey, $usedTeacherSlots[$as->teacher_id])) return false;

        // Room assignment: ONLY Practice/PE subjects get a room ID
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
            if (!$roomFoundId) return false; // Fail if no room available for practical subject
        }

        return true;
    }

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

    private function getScheduleName(): string
    {
        $semester   = Setting::getVal('semester', 'Học kỳ 1');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        return $semester . ' - ' . $schoolYear;
    }
}
