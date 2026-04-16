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
    public function teacher() 
    { 
        return $this->belongsTo(Teacher::class); 
    }

    public function subject() 
    { 
        return $this->belongsTo(Subject::class); 
    }

    public function classroom() 
    { 
        return $this->belongsTo(Classroom::class, 'class_id'); 
    }
}