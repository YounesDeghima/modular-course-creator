<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    protected $fillable = ['user_id', 'course_id', 'forced'];

    protected $casts = [
        'forced' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(user::class);
    }

    public function course()
    {
        return $this->belongsTo(course::class);
    }
}
