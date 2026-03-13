 <?php
use App\Models\Schedule;
use App\Models\Assignment;
use Illuminate\Http\Request;

Route::post('/check-schedule', function (Request $request) {
    $assign = Assignment::find($request->assignment_id);
    
    // 1. Kiểm tra trùng lịch Giáo viên
    $duplicateTeacher = Schedule::where('day_of_week', $request->day)
        ->where('period', $request->period)
        ->whereHas('assignment', function($q) use ($assign) {
            $q->where('teacher_id', $assign->teacher_id);
        })->exists();

    if ($duplicateTeacher) {
        return response()->json(['success' => false, 'message' => 'Giáo viên này đã có tiết dạy ở lớp khác!']);
    }

    // 2. Lưu vào database nếu hợp lệ
    Schedule::create([
        'schedule_name' => 'Học kỳ 1',
        'assignment_id' => $request->assignment_id,
        'day_of_week' => $request->day,
        'period' => $request->period,
    ]);

    return response()->json(['success' => true]);
});