<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
