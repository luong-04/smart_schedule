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
            $table->index(['schedule_name', 'day_of_week', 'period']);
            $table->index('room_id');
            $table->index('assignment_id');
            // Chống Race Condition khi lưu lịch
            $table->unique(['schedule_name', 'assignment_id', 'day_of_week', 'period'], 'schedules_unique_assignment');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->index(['class_id', 'teacher_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique('schedules_unique_assignment');
            $table->dropIndex(['schedule_name', 'day_of_week', 'period']);
            $table->dropIndex(['room_id']);
            $table->dropIndex(['assignment_id']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['class_id', 'teacher_id']);
        });
    }
};
