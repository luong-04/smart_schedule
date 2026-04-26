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
            // Chuyển đổi ràng buộc UNIQUE thành INDEX thường để tùy biến logic qua phần mềm
            $table->dropUnique('room_slot_unique_v3');
            $table->dropUnique('teacher_slot_unique_v3');

            // Thêm các Index để tối ưu hiệu năng truy vấn và báo báo xung đột
            $table->index(['schedule_name', 'applies_from', 'room_id', 'day_of_week', 'period'], 'room_slot_idx_v3');
            $table->index(['schedule_name', 'applies_from', 'teacher_id', 'day_of_week', 'period'], 'teacher_slot_idx_v3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('room_slot_idx_v3');
            $table->dropIndex('teacher_slot_idx_v3');

            $table->unique(['schedule_name', 'applies_from', 'room_id', 'day_of_week', 'period'], 'room_slot_unique_v3');
            $table->unique(['schedule_name', 'applies_from', 'teacher_id', 'day_of_week', 'period'], 'teacher_slot_unique_v3');
        });
    }
};
