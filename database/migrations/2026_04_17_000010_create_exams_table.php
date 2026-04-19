<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng thông tin kỳ thi (Dùng cho module phân công giám thị)
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Tên kỳ thi
            $table->date('start_date'); // Ngày bắt đầu
            $table->integer('total_days'); // Tổng số ngày thi
            $table->integer('rooms_per_day'); // Số phòng thi mỗi ngày
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
