<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'schedule_name',
        'applies_from',
        'applies_to',
        'assignment_id',
        'teacher_id',
        'room_id',
        'day_of_week',
        'period',
        'class_id'
    ];

    protected $casts = [
        'applies_from' => 'date',
        'applies_to'   => 'date',
    ];

    /**
     * Lấy thông tin phân công giảng dạy liên quan đến tiết này.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignment()
    {
        return $this->belongsTo(Assignment::class)->withTrashed()->withDefault();
    }

    /**
     * Lấy thông tin phòng học được gán cho tiết học này.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function room()
    {
        return $this->belongsTo(Room::class)->withDefault([
            'name' => 'N/A (Đã xóa)',
        ]);
    }
}