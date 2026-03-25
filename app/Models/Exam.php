<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    // Cho phép thêm dữ liệu hàng loạt vào tất cả các cột
    protected $guarded = [];

    // Quan hệ: Một kỳ thi có nhiều Giám thị import
    public function proctors()
    {
        return $this->hasMany(ExamProctor::class);
    }

    // Quan hệ: Một kỳ thi có nhiều Bản phân công
    public function assignments()
    {
        return $this->hasMany(ProctorAssignment::class);
    }
}