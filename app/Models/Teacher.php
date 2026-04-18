<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'department',
        'max_slots_week',
        'off_days'
    ];

    protected $casts = [
        'off_days' => 'array' // Tự động convert JSON <-> Array
    ];

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
    public function getRemainingSlotsAttribute() {
        // Tối ưu: Truy vấn trực tiếp từ bảng schedules bằng teacher_id đã denormalize
        // Chỉ đếm các tiết thuộc phân công chưa bị xóa (nếu cần ràng buộc chặt chẽ hơn)
        $used = \App\Models\Schedule::where('teacher_id', $this->id)
            ->whereHas('assignment') // whereHas mặc định không lấy Assignment đã soft-deleted
            ->count();
        
        return max(0, $this->max_slots_week - $used);
    }
    
}