<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Thêm SoftDeletes cho tables gốc
        Schema::table('teachers', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('classes', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('subjects', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('assignments', function (Blueprint $table) { $table->softDeletes(); });

        // 2. Thêm teacher_id vào bảng schedules (nullable ban đầu)
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->onDelete('cascade');
        });

        // 3. Backfill dữ liệu teacher_id cho schedules hiện có
        DB::statement('
            UPDATE schedules s
            JOIN assignments a ON s.assignment_id = a.id
            SET s.teacher_id = a.teacher_id
        ');

        // 4. Thêm foreign key onDelete('cascade') cho assignments trong schedules
        Schema::table('schedules', function (Blueprint $table) {
            // Drop khóa ngoại cũ
            $table->dropForeign(['assignment_id']);
            // Tạo lại khóa ngoại với cascade
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
        });

        // 5. Thêm Composite Unique Index để chặn Race Condition
        Schema::table('schedules', function (Blueprint $table) {
            $table->unique(['schedule_name', 'day_of_week', 'period', 'teacher_id'], 'unique_teacher_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique('unique_teacher_schedule');
            $table->dropForeign(['assignment_id']);
            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('teacher_id');
        });

        Schema::table('assignments', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('subjects', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('classes', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('teachers', function (Blueprint $table) { $table->dropSoftDeletes(); });
    }
};
