<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

// Các Controller của bạn
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\CurriculumController; 
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ProctorController; 
use App\Http\Controllers\Admin\UserController; 

Route::get('/', function () { 
    return view('welcome'); 
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth'])->name('dashboard');

// Route Quản lý tài khoản cá nhân (Ai đăng nhập cũng đổi pass được)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==========================================
// TOÀN BỘ ROUTE ADMIN (ĐÃ PHÂN QUYỀN ROUTE)
// ==========================================
Route::prefix('admin')->middleware(['auth'])->group(function () {
    
    // 1. NHÓM DÀNH CHO TẤT CẢ NHÂN VIÊN (Chỉ cần đăng nhập là xem được)
    Route::get('/dashboard', [ScheduleController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/schedules/list', [ScheduleController::class, 'list'])->name('schedules.list');
    Route::get('/schedules/view/{class_id}', [ScheduleController::class, 'show'])->name('schedules.show');

    // 2. NHÓM GIÁO VIÊN
    Route::middleware(['can:quan_ly_giao_vien'])->group(function () {
        Route::resource('teachers', TeacherController::class);
    });

    // 3. NHÓM MÔN HỌC
    Route::middleware(['can:quan_ly_mon_hoc'])->group(function () {
        Route::resource('subjects', SubjectController::class);
    });

    // 4. NHÓM LỚP HỌC & PHÒNG HỌC
    Route::middleware(['can:quan_ly_lop_hoc'])->group(function () {
        Route::resource('classrooms', ClassroomController::class);
        Route::resource('room-types', RoomTypeController::class);
        Route::resource('rooms', RoomController::class);
    });

    // 5. NHÓM XẾP LỊCH (Phân công, Ma trận, Chương trình)
    Route::middleware(['can:quan_ly_xep_lich'])->group(function () {
        Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        Route::get('/matrix', [ScheduleController::class, 'index'])->name('matrix.index');
        Route::post('/matrix/save', [ScheduleController::class, 'save'])->name('admin.schedules.save');

        Route::resource('curriculum', CurriculumController::class);
    });

    // 6. NHÓM GIÁM THỊ
    Route::middleware(['can:quan_ly_giam_thi'])->group(function () {
        Route::get('/proctors', [ProctorController::class, 'index'])->name('admin.proctors.index');
        Route::post('/proctors/assign', [ProctorController::class, 'assign'])->name('admin.proctors.assign');
        Route::get('/proctors/history', [ProctorController::class, 'history'])->name('admin.proctors.history');
        Route::delete('/proctors/{id}', [ProctorController::class, 'destroy'])->name('proctors.destroy');
    });

    // 7. NHÓM SUPER ADMIN (Tạo nhân viên, Cài đặt hệ thống)
    Route::middleware(['role:Super Admin'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
        
        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/auth.php';