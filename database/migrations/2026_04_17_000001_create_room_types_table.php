<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng loại phòng học (Lý thuyết, Thực hành, Nhà đa năng,...)
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên loại phòng
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
