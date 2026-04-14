<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Kiểm tra đúng tên bảng là 'classes'
        if (Schema::hasTable('classes') && !Schema::hasColumn('classes', 'block')) {
            Schema::table('classes', function (Blueprint $table) {
                // Thêm cột block, mặc định là Cơ bản
                $table->string('block')->default('Cơ bản')->after('grade');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'block')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropColumn('block');
            });
        }
    }
};