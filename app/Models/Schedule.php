<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'schedule_name',
        'assignment_id',
        'teacher_id',
        'room_id',
        'day_of_week',
        'period'
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class)->withTrashed()->withDefault();
    }

    // BỔ SUNG HÀM NÀY ĐỂ FIX LỖI RELATION NOT FOUND
    public function room()
    {
        return $this->belongsTo(Room::class)->withDefault([
            'name' => 'N/A (Đã xóa)',
        ]);
    }
}