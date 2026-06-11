<?php

use App\Models\course;
use App\Models\lesson;
use App\Models\user;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $user;
    public $totalUsers;
    public $totalCourses;
    public $pubCourses;
    public $draftCourses;
    public $pubLessons;
    public $draftLessons;
    public $inProgress;
    public $completed;

    public $assignedSections;
    public $myCourses;


    public function mount($user)
    {

        $this->user = $user;
        $this->loadstats();
    }




    public function loadstats()
    {
        $this->totalCourses = course::count();
        if($this->user->role=='admin'||$this->user->role == 'teacher')
        {
            $this->totalUsers = user::count();

            $this->pubCourses = course::where('status', '=', 'published')->count();
            $this->draftCourses = course::where('status', '=', 'draft')->count();
            $this->pubLessons = lesson::where('status', '=', 'published')->count();
            $this->draftLessons = lesson::where('status', '=', 'draft')->count();
            $this->assignedSections= $this->user->assignedsections()->count();

        }


        else{
            $this->inProgress = 0;
            $this->completed  = 0;

            foreach (course::all() as $course) {
                $progress = $course->progressForUser($this->user->id);
                if ($progress == 100) $this->completed++;
                elseif ($progress > 0) $this->inProgress++;
            }
        }


        $this->dispatch("stats-updated" ,

            totalCourses: $this->totalCourses,
            pubCourses: $this->pubCourses,
            draftCourses: $this->draftCourses,
            pubLessons: $this->pubLessons,
            draftLessons: $this->draftLessons,
            inProgress: $this->inProgress,
            completed: $this->completed,
            totalUsers: $this->totalUsers,
            assignedSections:$this->assignedSections

        );

    }

};
?>

<div class="dash-stats" wire:poll.5s="loadstats">
    @if($user->role=='admin'||$user->role == 'teacher')
        <div class="stat-card">
            @if($user->role == 'admin')
                <div class="stat-label">Total users</div>
                <div class="stat-val">{{ $totalUsers }}</div>
                <div class="stat-sub">registered accounts</div>
            @else
                <div class="stat-label">Assigned Sections</div>
                <div class="stat-val">{{ $assignedSections }}</div>
                <div class="stat-sub">registered sections</div>
            @endif

        </div>
        <div class="stat-card">
            <div class="stat-label">Courses</div>
            <div class="stat-val">{{ $totalCourses }}</div>
            <div class="stat-sub">{{ $pubCourses }} published · {{ $draftCourses }} draft</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Published lessons</div>
            <div class="stat-val">{{ $pubLessons }}</div>
            <div class="stat-sub">live for students</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Draft lessons</div>
            <div class="stat-val">{{ $draftLessons }}</div>
            <div class="stat-sub">awaiting publish</div>
        </div>

    @else

        <div class="stat-card">
            <div class="stat-label">Total courses</div>
            <div class="stat-val">{{ $totalCourses }}</div>
            <div class="stat-sub">published courses</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Completed</div>
            <div class="stat-val">{{ $completed }}</div>
            <div class="stat-sub">fully finished</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">In progress</div>
            <div class="stat-val">{{ $inProgress }}</div>
            <div class="stat-sub">keep going</div>
        </div>
    @endif


</div>
