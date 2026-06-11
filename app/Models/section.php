<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class section extends Model
{
    protected $fillable = [
        'section_number'
    ];
    public function students(){
        return $this->hasMany(user::class,'section_id');
    }

    public function student_count(){
        return $this->students()->count();
    }
}

