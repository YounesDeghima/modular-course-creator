<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class questionchoice extends Model
{
    use hasFactory;

    protected $fillable = [
        'content',
        'coursequestion_id',
        'value',
    ];
    public $timestamps = false;
}
