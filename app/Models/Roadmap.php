<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roadmap extends Model
{
    protected $fillable = ['title', 'description', 'tag', 'status'];

    public function levels()
    {
        return $this->hasMany(RoadmapLevel::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(RoadmapEnrollment::class);
    }

    public function enrolledUsers()
    {
        return $this->belongsToMany(user::class, 'roadmap_enrollments', 'roadmap_id', 'user_id')
                    ->withPivot('forced')
                    ->withTimestamps();
    }

    /**
     * All course IDs that belong to this roadmap (across all levels).
     */
    public function allCourseIds(): array
    {
        return RoadmapLevelCourse::whereIn('level_id', $this->levels()->pluck('id'))
            ->pluck('course_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Overall progress for a user: average of all courses in the roadmap.
     */
    public function progressForUser(int $userId): int
    {
        $courseIds = $this->allCourseIds();
        if (empty($courseIds)) return 0;

        $total = 0;
        foreach ($courseIds as $courseId) {
            $course = course::find($courseId);
            $total += $course ? $course->progressForUser($userId) : 0;
        }

        return (int) round($total / count($courseIds));
    }

    /**
     * Progress per level for a user. Returns array indexed by level order.
     */
    public function progressPerLevelForUser(int $userId): array
    {
        $result = [];
        foreach ($this->levels as $level) {
            $result[$level->order] = $level->progressForUser($userId);
        }
        return $result;
    }

    /**
     * The highest level index (1-based) that is fully unlocked for this user.
     * Level N is unlocked when level N-1 is 100% complete (all courses).
     * Level 1 is always accessible if enrolled.
     */
    public function currentLevelForUser(int $userId): int
    {
        $levels = $this->levels;
        $current = 1;
        foreach ($levels as $level) {
            if ($level->isCompleteForUser($userId)) {
                $current = $level->order + 1;
            } else {
                break;
            }
        }
        return $current;
    }
}
