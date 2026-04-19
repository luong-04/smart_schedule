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
    /**
     * Giám thị này thuộc về một kỳ thi cụ thể.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // Quan hệ: Giám thị này có thể được phân công nhiều lần (nhiều ngày/nhiều phòng)
    /**
     * Giám thị này có thể được phân công nhiều lần trong một hoặc nhiều ngày/phòng.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignments()
    {
        return $this->hasMany(ProctorAssignment::class);
    }
}