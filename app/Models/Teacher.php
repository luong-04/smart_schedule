<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    // Cho phép lưu dữ liệu hàng loạt cho các trường này
    protected $fillable = [
        'name',
        'code',
        'max_slots_week'
    ];

    /**
     * Liên kết với bảng Phân công (Assignments)
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}