<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    // Khai báo tên bảng nếu khác với tên Model số nhiều
    protected $table = 'classes'; 

    protected $fillable = [
        'name',
        'grade',
        'shift',
        'homeroom_teacher',
        'block'
    ];

    // Một lớp có nhiều phân công giảng dạy
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }
}