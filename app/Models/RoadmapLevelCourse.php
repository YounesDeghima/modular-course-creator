<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapLevelCourse extends Model
{
    protected $fillable = ['level_id', 'course_id'];

    public function level()
    {
        return $this->belongsTo(RoadmapLevel::class, 'level_id');
    }

    public function course()
    {
        return $this->belongsTo(course::class);
    }
}
