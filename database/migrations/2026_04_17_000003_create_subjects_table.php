<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng môn học
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Tên môn học
            $table->enum('type', ['theory', 'practice'])->default('theory'); // Loại môn (Lý thuyết/Thực hành)
            $table->unsignedBigInteger('room_type_id')->nullable(); // Loại phòng yêu cầu (nếu thực hành)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
