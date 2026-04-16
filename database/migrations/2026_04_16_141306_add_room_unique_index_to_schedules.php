<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Chống trùng phòng học: Một phòng không thể có 2 lớp cùng lúc trong cùng học kỳ
            $table->unique(['schedule_name', 'room_id', 'day_of_week', 'period'], 'unique_room_schedule');
            
            // Index bổ sung cho các câu truy vấn hay dùng WHERE
            $table->index('teacher_id');
        });
        
        Schema::table('assignments', function (Blueprint $table) {
            $table->index('subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique('unique_room_schedule');
            $table->dropIndex(['teacher_id']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['subject_id']);
        });
    }
};
