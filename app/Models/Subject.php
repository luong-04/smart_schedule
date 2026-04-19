<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    // Bổ sung thêm room_type_id vào mảng fillable để cho phép lưu
    protected $fillable = [
        'name', 
        'type', 
        'room_type_id'
    ];

    // Tạo mối quan hệ: 1 Môn học sẽ thuộc về 1 Loại phòng (Nếu có)
    /**
     * Lấy thông tin loại phòng học yêu cầu cho môn học này (nếu là môn thực hành).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}