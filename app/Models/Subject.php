<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    // Bổ sung thêm room_type_id vào mảng fillable để cho phép lưu
    protected $fillable = [
        'name', 
        'type', 
        'room_type_id'
    ];

    // Tạo mối quan hệ: 1 Môn học sẽ thuộc về 1 Loại phòng (Nếu có)
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}