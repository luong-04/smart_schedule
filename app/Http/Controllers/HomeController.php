<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Models\Schedule;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private function getScheduleName(): string
    {
        $semester   = Setting::getVal('semester', 'Học kỳ 1');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        return $semester . ' - ' . $schoolYear;
    }

    public function index(Request $request)
    {
        // 1. Lấy thông tin cấu hình cơ bản
        $schoolName = Setting::getVal('school_name', 'TRƯỜNG CHƯA CÀI ĐẶT');
        $schoolYear = Setting::getVal('school_year', '2024-2025');
        $semester   = Setting::getVal('semester', 'Học kỳ 1');
        $scheduleName = $this->getScheduleName();
        
        $schoolAddress = Setting::getVal('school_address', 'Chưa cập nhật địa chỉ');
        $schoolPhone   = Setting::getVal('school_phone', 'Chưa cập nhật SĐT');
        $schoolEmail   = Setting::getVal('school_email', 'Chưa cập nhật Email');
        $principal     = Setting::getVal('principal_name', 'Đang cập nhật');
        $vicePrincipal = Setting::getVal('vice_principal_name', 'Đang cập nhật');

        $assignFlag    = Setting::getVal('assign_gvcn_flag_salute', 0);
        $assignMeeting = Setting::getVal('assign_gvcn_class_meeting', 0);

        // 2. Xử lý tra cứu
        $searchQuery = $request->get('q');
        $classroom   = null;
        $teacher     = null;
        $schedules   = collect();
        $gvcnClasses = collect();
        $shiftVars   = [];
        $gvcnName    = null;

        if ($searchQuery) {
            // Thử tìm theo Lớp
            $classroom = Classroom::where('name', $searchQuery)->first();
            
            if ($classroom) {
                $schedules = Schedule::where('schedule_name', $scheduleName)
                    ->whereHas('assignment', function($query) use ($classroom) {
                        $query->where('class_id', $classroom->id);
                    })
                    ->with(['assignment.subject', 'assignment.teacher', 'room'])
                    ->get();
                    
                $shiftStr = strtolower($classroom->shift ?? 'morning');
                $shiftVars = [
                    'fDay' => Setting::getVal($shiftStr.'_flag_day', 2),
                    'fPer' => Setting::getVal($shiftStr.'_flag_period', ($shiftStr == 'morning' ? 1 : 10)),
                    'mDay' => Setting::getVal($shiftStr.'_meeting_day', 7),
                    'mPer' => Setting::getVal($shiftStr.'_meeting_period', ($shiftStr == 'morning' ? 5 : 10)),
                ];
                
                $gvcnName = $classroom->homeroomTeacher?->name ?? 'Chưa cập nhật';
            } else {
                // Thử tìm theo Giáo viên
                $teacher = Teacher::where('code', $searchQuery)
                                  ->orWhere('name', 'LIKE', '%' . $searchQuery . '%')
                                  ->first();
                                  
                if ($teacher) {
                    $schedules = Schedule::where('schedule_name', $scheduleName)
                        ->whereHas('assignment', function($query) use ($teacher) {
                            $query->where('teacher_id', $teacher->id);
                        })
                        ->with(['assignment.subject', 'assignment.classroom', 'room'])
                        ->get();
                    
                    $gvcnClasses = Classroom::with('homeroomTeacher')
                        ->where('homeroom_teacher_id', $teacher->id)
                        ->get();
                }
            }
        }

        return view('welcome', compact(
            'schoolName', 'schoolYear', 'semester', 'scheduleName',
            'schoolAddress', 'schoolPhone', 'schoolEmail',
            'principal', 'vicePrincipal',
            'assignFlag', 'assignMeeting',
            'classroom', 'teacher', 'schedules', 'gvcnClasses', 'shiftVars', 'gvcnName',
            'searchQuery'
        ));
    }
}
