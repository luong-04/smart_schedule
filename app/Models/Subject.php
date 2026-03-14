<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    // Chỉ giữ lại Tên và Loại hình môn học
    protected $fillable = ['name', 'type'];
}