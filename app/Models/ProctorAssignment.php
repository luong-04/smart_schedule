<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProctorAssignment extends Model
{
    use HasFactory;

    // Cho phép thêm dữ liệu hàng loạt
    protected $guarded = [];

    // Quan hệ: Thuộc về kỳ thi nào
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // Quan hệ: Của giám thị nào
    public function proctor()
    {
        return $this->belongsTo(ExamProctor::class, 'exam_proctor_id');
    }
}