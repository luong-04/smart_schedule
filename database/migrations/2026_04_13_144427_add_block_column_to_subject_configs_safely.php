<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Kiểm tra và thêm cột 'block' cho bảng subject_configurations
        if (Schema::hasTable('subject_configurations') && !Schema::hasColumn('subject_configurations', 'block')) {
            Schema::table('subject_configurations', function (Blueprint $table) {
                $table->string('block')->default('Cơ bản')->after('grade');
            });
        }

        // 2. Kiểm tra và thêm cột 'block' cho bảng classrooms (phòng hờ nếu bảng này cũng bị thiếu)
        if (Schema::hasTable('classrooms') && !Schema::hasColumn('classrooms', 'block')) {
            Schema::table('classrooms', function (Blueprint $table) {
                $table->string('block')->default('Cơ bản')->after('grade');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('subject_configurations', 'block')) {
            Schema::table('subject_configurations', function (Blueprint $table) {
                $table->dropColumn('block');
            });
        }

        if (Schema::hasColumn('classrooms', 'block')) {
            Schema::table('classrooms', function (Blueprint $table) {
                $table->dropColumn('block');
            });
        }
    }
};