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

    /**
     * Một giáo viên có nhiều phân công giảng dạy (dạy nhiều môn ở nhiều lớp).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }


    /**
     * Thuộc tính ảo (Accessor) tính toán số tiết dạy còn lại của giáo viên trong tuần.
     * 
     * @return int Số tiết còn lại.
     */
    public function getRemainingSlotsAttribute() {
        // Tối ưu: Truy vấn trực tiếp từ bảng schedules bằng teacher_id đã denormalize
        // Chỉ đếm các tiết thuộc phân công chưa bị xóa (nếu cần ràng buộc chặt chẽ hơn)
        $used = \App\Models\Schedule::where('teacher_id', $this->id)
            ->whereHas('assignment') // whereHas mặc định không lấy Assignment đã soft-deleted
            ->count();
        
        return max(0, $this->max_slots_week - $used);
    }
    
}