<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Thêm cột homeroom_teacher_id
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
        });

        // Migrate dữ liệu hiện tại dựa theo name
        DB::statement('
            UPDATE classes c
            JOIN teachers t ON c.homeroom_teacher = t.name
            SET c.homeroom_teacher_id = t.id
        ');

        // Bỏ cột cũ
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('homeroom_teacher');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('homeroom_teacher')->nullable();
        });

        DB::statement('
            UPDATE classes c
            JOIN teachers t ON c.homeroom_teacher_id = t.id
            SET c.homeroom_teacher = t.name
        ');

        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['homeroom_teacher_id']);
            $table->dropColumn('homeroom_teacher_id');
        });
    }
};
