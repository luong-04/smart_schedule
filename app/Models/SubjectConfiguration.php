<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectConfiguration extends Model
{
    protected $fillable = ['subject_id', 'grade', 'slots_per_week'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}