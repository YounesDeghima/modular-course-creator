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
        'chapter_id'
    ];
    public $timestamps = false;



    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

}
