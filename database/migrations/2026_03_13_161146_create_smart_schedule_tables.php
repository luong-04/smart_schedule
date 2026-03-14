<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Danh mục Môn học (Lý thuyết/Thực hành)
        Schema::create('subjects', function (Blueprint $table) {
            $table->id(); // Đây là ID bạn dùng để truy cập dữ liệu, nó tự động sinh ra
            $table->string('name');
            // $table->string('code')->unique(); // Xóa hoặc chú thích dòng này để không bị lỗi NOT NULL
            $table->enum('type', ['theory', 'practice'])->default('theory');
            $table->timestamps();
        });

        // 2. Danh mục Loại phòng & Phòng học
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Lab, Sân tập, Nhà đa năng...
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Lab 1, Phòng máy 2...
            $table->foreignId('room_type_id')->constrained();
            $table->timestamps();
        });

        // 3. Danh mục Lớp học
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 10A1, 11B2...
            $table->integer('grade'); // 10, 11, 12
            $table->enum('shift', ['morning', 'afternoon']); // Ca sáng/chiều
            $table->string('homeroom_teacher')->nullable(); // Giáo viên chủ nhiệm
            $table->timestamps();
        });

        // 4. Danh mục Giáo viên
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('max_slots_week')->default(18); // Số tiết tối đa/tuần
            $table->timestamps();
        });

        // 5. BẢNG QUAN TRỌNG: Phân công giảng dạy (Assignment)
        // Gán trực tiếp: [Giáo viên] dạy [Môn] tại [Lớp]
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 6. Bảng lưu kết quả Thời khóa biểu (Ma trận kết quả)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_name'); // Tên bản lưu TKB (Học kỳ 1 - 2024)
            $table->foreignId('assignment_id')->constrained();
            $table->foreignId('room_id')->nullable()->constrained();
            $table->integer('day_of_week'); // 2 -> 7
            $table->integer('period'); // Tiết 1 -> 10
            $table->timestamps();
        });
        // 7. Bảng cấu hình yêu cầu cho từng môn học (Số tiết/tuần, lớp nào cần...)
        Schema::create('subject_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->integer('grade'); // 10, 11, hoặc 12
            $table->integer('slots_per_week')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Phải xóa theo thứ tự ngược lại để tránh lỗi khóa ngoại (Foreign Key)
        Schema::dropIfExists('subject_configurations');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('subjects');
    }
};
