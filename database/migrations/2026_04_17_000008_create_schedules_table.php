<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng lưu trữ Thời khóa biểu (Schedules)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_name')->index(); // Tên bản thời khóa biểu (Ví dụ: Học kỳ 1 - 2024)
            $table->date('applies_from')->index(); // Ngày bắt đầu áp dụng
            $table->date('applies_to')->index(); // Ngày kết thúc áp dụng
            
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade'); // Liên kết phân công giảng dạy
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null'); // ID Phòng học (nếu có)
            
            // Trường denormalize để tăng tốc truy vấn ma trận và check xung đột
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade'); // ID Giáo viên (denormalized)
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade'); // ID Lớp học (denormalized)
            
            $table->integer('day_of_week'); // Thứ trong tuần (2-7)
            $table->integer('period');      // Tiết trong ngày (1-10)
            $table->timestamps();

            // Unique Constraints - Chống Race Condition tuyệt đối & Đảm bảo tính toàn vẹn temporal
            $table->unique(['schedule_name', 'applies_from', 'class_id', 'day_of_week', 'period'], 'class_slot_unique_v3');
            $table->unique(['schedule_name', 'applies_from', 'teacher_id', 'day_of_week', 'period'], 'teacher_slot_unique_v3');
            $table->unique(['schedule_name', 'applies_from', 'room_id', 'day_of_week', 'period'], 'room_slot_unique_v3');
            
            // Index dải ngày cho tìm kiếm phiên bản lịch
            $table->index(['applies_from', 'applies_to'], 'idx_temporal_range_v3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
