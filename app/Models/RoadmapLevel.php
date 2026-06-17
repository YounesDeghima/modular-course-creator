<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapLevel extends Model
{
    protected $fillable = ['roadmap_id', 'order'];

    public function roadmap()
    {
        return $this->belongsTo(Roadmap::class);
    }

    public function levelCourses()
    {
        return $this->hasMany(RoadmapLevelCourse::class, 'level_id');
    }

    public function courses()
    {
        return $this->belongsToMany(course::class, 'roadmap_level_courses', 'level_id', 'course_id');
    }

    /**
     * Average progress across all courses in this level for a user.
     */
    public function progressForUser(int $userId): int
    {
        $courses = $this->courses;
        if ($courses->isEmpty()) return 0;

        $total = $courses->sum(fn($c) => $c->progressForUser($userId));
        return (int) round($total / $courses->count());
    }

    /**
     * Level is complete when ALL courses are >= 100%.
     */
    public function isCompleteForUser(int $userId): bool
    {
        $courses = $this->courses;
        if ($courses->isEmpty()) return false;

        foreach ($courses as $course) {
            if ($course->progressForUser($userId) < 100) return false;
        }
        return true;
    }
}
