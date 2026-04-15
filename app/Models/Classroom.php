<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    // Khai báo tên bảng nếu khác với tên Model số nhiều
    protected $table = 'classes';

    /**
     * Các hằng số cho loại Tổ hợp (Block)
     * Dùng thay cho hardcode string 'Cơ bản', 'KHTN', 'KHXH'
     */
    const BLOCK_CO_BAN = 'Cơ bản';
    const BLOCK_KHTN   = 'KHTN';
    const BLOCK_KHXH   = 'KHXH';

    /**
     * Danh sách tất cả các loại block hợp lệ
     */
    const BLOCKS = [
        self::BLOCK_CO_BAN,
        self::BLOCK_KHTN,
        self::BLOCK_KHXH,
    ];

    protected $fillable = [
        'name',
        'grade',
        'shift',
        'homeroom_teacher_id',
        'block'
    ];

    /**
     * Lấy block name với fallback an toàn (tránh null)
     */
    public function getBlockNameAttribute(): string
    {
        return $this->block ?? self::BLOCK_CO_BAN;
    }

    // Một lớp có nhiều phân công giảng dạy
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }

    /**
     * Quan hệ tới GVCN thông qua ID chuẩn
     */
    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }
}