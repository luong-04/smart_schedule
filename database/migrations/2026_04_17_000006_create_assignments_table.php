<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng phân công giảng dạy (Liên kết Giáo viên - Môn học - Lớp học)
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade'); // ID Giáo viên
            $table->foreignId('subject_id')->constrained()->onDelete('cascade'); // ID Môn học
            $table->foreignId('class_id')->constrained()->onDelete('cascade'); // ID Lớp học
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
