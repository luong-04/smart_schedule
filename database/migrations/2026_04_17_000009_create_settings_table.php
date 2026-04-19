<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng cài đặt hệ thống (Cấu hình chào cờ, sinh hoạt, ràng buộc,...)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Khóa cài đặt (Ví dụ: morning_flag_day)
            $table->text('value')->nullable(); // Giá trị của cài đặt
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
