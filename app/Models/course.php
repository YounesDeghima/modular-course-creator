<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class course extends Model
{
    use HasFactory;
    protected $fillable =[
        'title',
        'year',
        'branch',
        'description',
        'status'

    ];
    public $timestamps = false;

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    public function progressForUser($userId)
    {
        $chapter_ids = $this->chapters()->where('status', 'published')->pluck('id');
        $lessons = lesson::wherein('chapter_id',$chapter_ids)

            ->where('status', 'published')->get();


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

        return ($completedLessons / $lessons->count()) * 100;
    }
}
