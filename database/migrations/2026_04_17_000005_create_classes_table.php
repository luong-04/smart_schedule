<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng lớp học
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Tên lớp (Ví dụ: 10A1)
            $table->integer('grade')->index(); // Khối (10, 11, 12)
            $table->string('block')->nullable()->index(); // Tổ hợp (KHTN, KHXH, Cơ bản)
            $table->enum('shift', ['morning', 'afternoon']); // Buổi học (Sáng/Chiều)
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('teachers')->onDelete('set null'); // GVCN
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
