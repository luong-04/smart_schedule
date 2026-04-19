<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    // Danh sách các trường được phép lưu dữ liệu hàng loạt
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'class_id',
    ];

    /**
     * Các quan hệ (Relationships)
     */

    /**
     * Lấy thông tin giáo viên được phân công.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function teacher() 
    { 
        return $this->belongsTo(Teacher::class)->withTrashed()->withDefault([
            'name' => 'N/A (Đã xóa)',
            'code' => 'N/A',
        ]); 
    }

    /**
     * Lấy thông tin môn học được phân công.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subject() 
    { 
        return $this->belongsTo(Subject::class)->withTrashed()->withDefault([
            'name' => 'Môn học (Đã xóa)',
        ]); 
    }

    /**
     * Lấy thông tin lớp học được phân công.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom() 
    { 
        return $this->belongsTo(Classroom::class, 'class_id')->withDefault([
            'name' => 'Lớp học (Đã xóa)',
        ]); 
    }
}