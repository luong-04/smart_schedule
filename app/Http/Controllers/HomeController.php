<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Teacher;
use App\Models\Schedule;
use App\Models\Setting;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private function getScheduleName(): string
    {
        // 1. Thử lấy từ setting 'active_schedule'
        $configured = Setting::getVal('active_schedule');
        if ($configured && Schedule::where('schedule_name', $configured)->exists()) {
            return $configured;
        }

        // 2. Thử ghép từ Học kỳ - Niên khóa
        $semester   = Setting::getVal('semester', 'Học kỳ 1');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        $builtName  = $semester . ' - ' . $schoolYear;
        
        if (Schedule::where('schedule_name', $builtName)->exists()) {
            return $builtName;
        }

        // 3. Fallback: Lấy TKB mới nhất có trong hệ thống
        $latest = Schedule::latest('updated_at')->first();
        if ($latest) {
            return $latest->schedule_name;
        }

        return $builtName;
    }

    public function index(Request $request)
    {
        $settings      = Setting::pluck('value', 'key')->all();
        $schoolName    = $settings['school_name']    ?? 'TRƯỜNG THPT X';
        $schoolYear    = $settings['school_year']    ?? '2023-2024';
        $semester      = $settings['semester']       ?? 'Học kỳ I';
        
        $scheduleName  = $this->getScheduleName();
        
        $principal     = $settings['principal_name']      ?? 'Nguyễn Văn A';
        $vicePrincipal = $settings['vice_principal_name'] ?? 'Trần Thị B';

        $assignFlag    = $settings['assign_gvcn_flag_salute']    ?? 0;
        $assignMeeting = $settings['assign_gvcn_class_meeting']  ?? 0;

        $searchQuery = trim($request->get('q', ''));
        $selectedDate = $request->get('date', now()->toDateString());
        
        $classroom   = null;
        $teacher     = null;
        $schedules   = collect();
        $gvcnClasses = collect();
        $gvcnName    = null;
        $shiftVars   = [];

        $appliesFromDate = null;
        $appliesToDate   = null;

        if ($searchQuery) {
            // Thử tìm theo Lớp
            $classroom = Classroom::where('name', $searchQuery)->first();
            
            if ($classroom) {
                // 1. Thử tìm TKB bao gồm ngày được chọn
                $schedules = Schedule::where('schedule_name', $scheduleName)
                    ->where('class_id', $classroom->id)
                    ->where('applies_from', '<=', $selectedDate)
                    ->where('applies_to', '>=', $selectedDate)
                    ->with(['assignment.subject', 'assignment.teacher', 'room'])
                    ->get();
                
                // 2. FALLBACK (Evergreen): Nếu ngày được chọn chưa có bản lịch riêng, lấy bản mới nhất trong quá khứ
                if ($schedules->isEmpty()) {
                    $latestPastFrom = Schedule::where('schedule_name', $scheduleName)
                        ->where('class_id', $classroom->id)
                        ->where('applies_from', '<=', $selectedDate)
                        ->max('applies_from');
                    
                    if ($latestPastFrom) {
                        $schedules = Schedule::where('schedule_name', $scheduleName)
                            ->where('class_id', $classroom->id)
                            ->where('applies_from', $latestPastFrom)
                            ->with(['assignment.subject', 'assignment.teacher', 'room'])
                            ->get();
                    }
                }

                $appliesFromDate = $schedules->first()?->applies_from;
                $appliesToDate   = $schedules->first()?->applies_to;
                    
                $shiftStr = strtolower($classroom->shift ?? 'morning');
                $shiftVars = [
                    'fDay' => Setting::getVal($shiftStr.'_flag_day', 2),
                    'fPer' => Setting::getVal($shiftStr.'_flag_period', ($shiftStr == 'morning' ? 1 : 10)),
                    'mDay' => Setting::getVal($shiftStr.'_meeting_day', 7),
                    'mPer' => Setting::getVal($shiftStr.'_meeting_period', ($shiftStr == 'morning' ? 5 : 10))
                ];
                
                if ($classroom->homeroomTeacher) {
                    $gvcnName = $classroom->homeroomTeacher->name;
                }
                
            } else {
                // Thử tìm theo Giáo viên
                $teacher = Teacher::where('name', 'LIKE', "%{$searchQuery}%")
                                  ->orWhere('code', $searchQuery)
                                  ->first();
                                  
                if ($teacher) {
                    // 1. Thử tìm TKB bao gồm ngày được chọn
                    $schedules = Schedule::where('schedule_name', $scheduleName)
                        ->where('teacher_id', $teacher->id)
                        ->where('applies_from', '<=', $selectedDate)
                        ->where('applies_to', '>=', $selectedDate)
                        ->with(['assignment.subject', 'assignment.classroom', 'room'])
                        ->get();

                    // 2. FALLBACK (Evergreen)
                    if ($schedules->isEmpty()) {
                        $latestPastFrom = Schedule::where('schedule_name', $scheduleName)
                            ->where('teacher_id', $teacher->id)
                            ->where('applies_from', '<=', $selectedDate)
                            ->max('applies_from');
                        
                        if ($latestPastFrom) {
                            $schedules = Schedule::where('schedule_name', $scheduleName)
                                ->where('teacher_id', $teacher->id)
                                ->where('applies_from', $latestPastFrom)
                                ->with(['assignment.subject', 'assignment.classroom', 'room'])
                                ->get();
                        }
                    }

                    $appliesFromDate = $schedules->first()?->applies_from;
                    $appliesToDate   = $schedules->first()?->applies_to;
                    
                    $gvcnClasses = Classroom::with('homeroomTeacher')
                        ->where('homeroom_teacher_id', $teacher->id)
                        ->get();
                }
            }
        }

        return view('welcome', compact(
            'schoolName', 'schoolYear', 'semester', 'scheduleName',
            'principal', 'vicePrincipal',
            'searchQuery', 'classroom', 'teacher', 'schedules', 'gvcnClasses', 'gvcnName',
            'shiftVars', 'assignFlag', 'assignMeeting', 'appliesFromDate', 'appliesToDate', 'selectedDate'
        ));
    }
}
