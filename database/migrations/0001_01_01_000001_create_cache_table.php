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
        // Bảng lưu trữ Cache hệ thống
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary(); // Khóa cache
            $table->mediumText('value'); // Giá trị cache
            $table->integer('expiration')->index(); // Thời gian hết hạn
        });

        // Bảng lưu trữ các khóa lock (chống race condition khi ghi cache)
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
