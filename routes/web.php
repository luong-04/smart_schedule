<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\CurriculumController; 
use App\Http\Controllers\Admin\SettingsController;
// ---> THÊM KHAI BÁO CONTROLLER NÀY <---
use App\Http\Controllers\Admin\ProctorController; 

Route::get('/', function () { return view('welcome'); });

Route::prefix('admin')->group(function () {
    // 1. Dashboard
    Route::get('/dashboard', [ScheduleController::class, 'dashboard'])->name('admin.dashboard');

    // 2. Giáo viên (CRUD)
    Route::resource('teachers', TeacherController::class);

    // 3. Môn học (CRUD)
    Route::resource('subjects', SubjectController::class);

    // 4. Lớp học (CRUD)
    Route::resource('classrooms', ClassroomController::class);

    // 5. Phân công giảng dạy
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // 6. Ma trận TKB (Smart Matrix)
    Route::get('/matrix', [ScheduleController::class, 'index'])->name('matrix.index');
    Route::post('/matrix/save', [ScheduleController::class, 'save'])->name('admin.schedules.save');

    Route::resource('room-types', RoomTypeController::class);
    Route::resource('rooms', RoomController::class);

    Route::resource('curriculum', CurriculumController::class);

    // 7. TKB đã sắp (Dành cho việc xem/in ấn/phát cho lớp)
    Route::get('/schedules/list', [ScheduleController::class, 'list'])->name('schedules.list');
    Route::get('/schedules/view/{class_id}', [ScheduleController::class, 'show'])->name('schedules.show');

    // 8. Cài đặt hệ thống
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');

    // ---> 9. PHÂN CÔNG GIÁM THỊ <---
    Route::get('/proctors', [ProctorController::class, 'index'])->name('admin.proctors.index');
    Route::post('/proctors/assign', [ProctorController::class, 'assign'])->name('admin.proctors.assign');
    Route::get('/proctors/history', [ProctorController::class, 'history'])->name('admin.proctors.history');
});