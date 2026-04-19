<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng phân công giám thị phòng thi
        Schema::create('proctor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade'); // ID Kỳ thi
            $table->foreignId('exam_proctor_id')->constrained()->onDelete('cascade'); // ID Giám thị
            $table->date('assign_date')->index(); // Ngày phân công
            $table->string('room_name'); // Tên phòng thi (Ví dụ: Phòng 01)
            $table->enum('role', ['GT1', 'GT2']); // Vai trò (Giám thị 1/Giám thị 2)
            $table->timestamps();

            // Index kép để tối ưu load lịch sử kỳ thi
            $table->index(['exam_id', 'assign_date'], 'exam_date_lookup_v3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proctor_assignments');
    }
};
