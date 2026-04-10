<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class coursequestion extends Model
{
    use hasFactory;

    protected $fillable = [
        'content',
        'course_id',

    ];
    public $timestamps = false;


    public function questionchoices(){

            return $this->hasMany(questionchoice::class);
    }

    public function course(){
        return $this->belongsTo(course::class);
    }
}


