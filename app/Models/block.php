<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\exercisesolution;

class block extends Model
{
    protected $fillable =[
        'name',
        'type',
        'content',
        'block_number',
        'lesson_id'
    ];
    public $timestamps = false;

    public function solutions()
    {
        return $this->hasMany(exercisesolution::class);
    }




}
