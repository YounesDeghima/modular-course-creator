<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lesson extends Model
{
    use HasFactory;
    protected $fillable =[
        'title',
        'description',
        'lesson_number',
        'chapter_id',
        'status'
    ];
    public $timestamps = false;



    public function blocks()
    {
        return $this->hasMany(Block::class);
    }
    public function lesson_progress()
    {
        return $this->hasMany(lesson_progress::class);
    }

    public function progressForUser($userId)
    {
        return $this->lesson_progress()
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

}
