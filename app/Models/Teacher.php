<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
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
        // Tổng tiết tối đa
        $max = $this->max_slots_week;
        
        // Đếm số tiết đã được xếp trong bảng schedules thông qua assignments
        $used = \App\Models\Schedule::whereHas('assignment', function($q) {
            $q->where('teacher_id', $this->id);
        })->count();
        
        return $max - $used;
    }
}