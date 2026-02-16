<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class course extends Model
{
    protected $fillable =[
        'title',
        'year',
        'branch',
        'description',


    ];
    public $timestamps = false;

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }
}
