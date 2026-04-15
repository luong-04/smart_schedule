 <?php
use App\Models\Schedule;
use App\Models\Assignment;
use App\Models\Setting;
use Illuminate\Http\Request;

Route::post('/check-schedule', function (Request $request) {
    $assign = Assignment::find($request->assignment_id);
    
    // Lấy tên lịch động từ Settings
    $semester = Setting::getVal('semester', 'Học kỳ 1');
    $schoolYear = Setting::getVal('school_year', '2024-2025');
    $scheduleName = $semester . ' - ' . $schoolYear;
    
    // 1. Kiểm tra trùng lịch Giáo viên
    $duplicateTeacher = Schedule::where('schedule_name', $scheduleName)
        ->where('day_of_week', $request->day)
        ->where('period', $request->period)
        ->whereHas('assignment', function($q) use ($assign) {
            $q->where('teacher_id', $assign->teacher_id);
        })->exists();

    if ($duplicateTeacher) {
        return response()->json(['success' => false, 'message' => 'Giáo viên này đã có tiết dạy ở lớp khác!']);
    }

    // 2. Lưu vào database nếu hợp lệ
    Schedule::create([
        'schedule_name' => $scheduleName,
        'assignment_id' => $request->assignment_id,
        'day_of_week' => $request->day,
        'period' => $request->period,
    ]);

    return response()->json(['success' => true]);
});