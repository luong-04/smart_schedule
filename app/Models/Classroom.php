<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

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
     * Truy xuất tên tổ hợp (block) của lớp học. Mặc định là 'Cơ bản' nếu để trống.
     * 
     * @return string
     */
    public function getBlockNameAttribute(): string
    {
        return $this->block ?? self::BLOCK_CO_BAN;
    }

    /**
     * Một lớp học có nhiều bản ghi phân công giảng dạy.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }

    /**
     * Lấy thông tin Giáo viên chủ nhiệm của lớp.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }
}