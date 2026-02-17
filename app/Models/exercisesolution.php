<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class exercisesolution extends Model
{
    protected $fillable=[
        'title',
        'solution_number',
        'block_id',

    ];

    public $timestamps = false;

    use HasFactory;

    public function block()
    {
        return $this->belongsTo(block::class);
    }
}
