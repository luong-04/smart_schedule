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
        // Bảng quản lý Tài khoản Người dùng
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên người dùng
            $table->string('email')->unique(); // Email đăng nhập
            $table->timestamp('email_verified_at')->nullable(); // Thời điểm xác thực email
            $table->string('password'); // Mật khẩu đã mã hóa
            $table->rememberToken(); // Token ghi nhớ đăng nhập
            $table->timestamps();
        });

        // Bảng lưu trữ mã token khôi phục mật khẩu
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Bảng lưu trữ thông tin phiên làm việc (Session)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
