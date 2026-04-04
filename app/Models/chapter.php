<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class chapter extends Model
{
    use HasFactory;
    protected $fillable =[
        'title',
        'description',
        'chapter_number',
        'course_id',
        'status'

    ];
    public $timestamps = false;

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)
            ->orderBy('lesson_number', 'asc');
    }

    public function progressForUser($userId)
    {
        $lessons = $this->lessons()->where('status', 'published')->get();

        if ($lessons->count() === 0) {
            return 0;
        }

        $completedLessons = 0;

        foreach ($lessons as $lesson) {
            $progress = $lesson->progressForUser($userId);

            if ($progress && $progress->progress >= 90) {
                $completedLessons++;
            }
        }

        return round(($completedLessons / $lessons->count()) * 100);
    }


}

