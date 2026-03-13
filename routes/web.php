<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\ScheduleController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/admin/teachers', [TeacherController::class, 'index'])->name('teachers.index');
Route::prefix('admin')->group(function () {
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
});
Route::get('/admin/matrix', [ScheduleController::class, 'index'])->name('matrix.index');