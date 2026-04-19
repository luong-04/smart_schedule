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
        // Bảng lưu trữ danh sách các công việc hàng đợi (Queue Jobs)
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue'); // Tên hàng đợi
            $table->longText('payload'); // Dữ liệu công việc
            $table->unsignedTinyInteger('attempts'); // Số lần thử lại
            $table->unsignedInteger('reserved_at')->nullable(); // Thời điểm đang xử lý
            $table->unsignedInteger('available_at'); // Thời điểm khả dụng
            $table->unsignedInteger('created_at');

            $table->index(['queue', 'reserved_at', 'available_at']);
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // Bảng lưu trữ các công việc bị lỗi (Failed Jobs)
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // Mã định danh duy nhất
            $table->text('connection'); // Kết nối DB
            $table->text('queue'); // Tên hàng đợi
            $table->longText('payload'); // Dữ liệu payload
            $table->longText('exception'); // Nội dung lỗi ngoại lệ
            $table->timestamp('failed_at')->useCurrent(); // Thời điểm lỗi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
