<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng giáo viên
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Họ tên giáo viên
            $table->string('code')->unique(); // Mã giáo viên (duy nhất)
            $table->string('department')->nullable()->index(); // Tổ chuyên môn
            $table->integer('max_slots_week')->default(18); // Số tiết dạy tối đa/tuần
            $table->json('off_days')->nullable(); // Các ngày đăng ký nghỉ (JSON)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
