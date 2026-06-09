<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class user extends Authenticatable
{

    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'role',
        'last_seen',
        'section'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];



    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_seen' => 'datetime',
    ];
    public function testDb()
    {
        $users = user::all(); // fetch all rows from users table
        dd($users);           // dump & die to see results
    }

    public function assignedsections()
    {
        return $this->belongsToMany(Section::class, 'assignedsections', 'user_id', 'section_id');
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(course::class, 'course_enrollments', 'user_id', 'course_id')
                    ->withPivot('forced')
                    ->withTimestamps();
    }

    public function isEnrolledIn($courseId): bool
    {
        return $this->enrolledCourses()->where('course_id', $courseId)->exists();
    }
}
