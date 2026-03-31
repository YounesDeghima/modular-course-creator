<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lesson_progress extends Model
{
    use HasFactory;
    protected $fillable =[
        'user_id',
        'lesson_id',
        'progress',
    ];
    public $timestamps = false;
}
