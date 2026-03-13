<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'schedule_name',
        'assignment_id',
        'room_id',
        'day_of_week',
        'period'
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}