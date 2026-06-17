<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapEnrollment extends Model
{
    protected $fillable = ['roadmap_id', 'user_id', 'forced'];

    protected $casts = ['forced' => 'boolean'];

    public function roadmap()
    {
        return $this->belongsTo(Roadmap::class);
    }

    public function user()
    {
        return $this->belongsTo(user::class);
    }
}
