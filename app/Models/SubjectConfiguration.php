<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectConfiguration extends Model
{
    use HasFactory;

    // BỔ SUNG 'block' VÀO MẢNG NÀY
    protected $fillable = [
        'subject_id',
        'grade',
        'block',
        'slots_per_week',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}