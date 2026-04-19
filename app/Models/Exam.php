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
    /**
     * Một kỳ thi có nhiều giám thị tham gia.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proctors()
    {
        return $this->hasMany(ExamProctor::class);
    }

    // Quan hệ: Một kỳ thi có nhiều Bản phân công
    /**
     * Một kỳ thi có nhiều bản ghi phân công giám thị.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignments()
    {
        return $this->hasMany(ProctorAssignment::class);
    }
}