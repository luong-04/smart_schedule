<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng phòng học
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Tên phòng
            $table->foreignId('room_type_id')->constrained()->onDelete('cascade'); // Liên kết loại phòng
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
