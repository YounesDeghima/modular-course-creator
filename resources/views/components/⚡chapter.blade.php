<?php

use App\Models\course;
use App\Models\lesson;
use App\Models\user;
use Livewire\Component;

new class extends Component {
    public $totalUsers;
    public $totalCourses;
    public $pubCourses;
    public $draftCourses;
    public $pubLessons;
    public $draftLessons;


    public function mount()
    {
        $this->loadstats();
    }


    public function loadstats()
    {
        $this->totalUsers = user::count();
        $this->totalCourses = course::count();
        $this->pubCourses = course::where('status', '=', 'published')->count();
        $this->draftCourses = course::where('status', '=', 'draft')->count();
        $this->pubLessons = lesson::where('status', '=', 'published')->count();
        $this->draftLessons = lesson::where('status', '=', 'draft')->count();
    }

};
?>

<div class="dash-stats" wire:poll.5s="loadstats">
    <div class="stat-card">
        <div class="stat-label">Total users</div>
        <div class="stat-val">{{ $totalUsers }}</div>
        <div class="stat-sub">registered accounts</div>
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
</div>
