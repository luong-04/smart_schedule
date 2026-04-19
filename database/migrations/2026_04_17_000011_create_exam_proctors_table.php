<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng danh sách giám thị tham gia kỳ thi
        Schema::create('exam_proctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade'); // ID Kỳ thi
            $table->string('proctor_name')->index(); // Tên giám thị
            $table->string('proctor_code')->nullable()->index(); // Mã giám thị
            $table->string('department')->nullable(); // Tổ chuyên môn
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_proctors');
    }
};
