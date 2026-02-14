<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lesson extends Model
{
    protected $fillable =[
        'name',
        'description',
        'lesson_number',
        'chapter_id'
    ];
    public $timestamps = false;



}
