<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proctor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_proctor_id')->constrained()->onDelete('cascade');
            $table->date('assign_date')->index();
            $table->string('room_name');
            $table->enum('role', ['GT1', 'GT2']);
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
