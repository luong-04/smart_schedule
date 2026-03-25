<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamProctor extends Model
{
    use HasFactory;

    // Cho phép thêm dữ liệu hàng loạt
    protected $guarded = [];

    // Quan hệ: Giám thị này thuộc về một kỳ thi cụ thể
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // Quan hệ: Giám thị này có thể được phân công nhiều lần (nhiều ngày/nhiều phòng)
    public function assignments()
    {
        return $this->hasMany(ProctorAssignment::class);
    }
}