<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class chapter_progress extends Model
{
    use HasFactory;
    protected $fillable =[
        'user_id',
        'chapter_id',
        'progress',
    ];
    public $timestamps = false;
}

