<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng định mức tiết học (Số tiết/tuần theo Khối và Tổ hợp)
        Schema::create('subject_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade'); // ID Môn học
            $table->integer('grade')->index(); // Khối (10, 11, 12)
            $table->string('block')->index(); // Tổ hợp (KHTN, KHXH, Cơ bản)
            $table->integer('slots_per_week')->default(0); // Số tiết quy định mỗi tuần
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_configurations');
    }
};
