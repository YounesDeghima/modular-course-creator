<?php

use App\Models\course;
use App\Models\lesson;
use Livewire\Component;

new class extends Component {
    public $user;
    public $totalUsers=0;
    public $totalCourses=0;
    public $pubCourses;
    public $draftCourses;
    public $pubLessons=0;
    public $draftLessons=0;
    public $inProgress;
    public $completed;


    protected $listeners = ['stats-updated'=>'reload'];

    public function mount($user)
    {

        $this->user = $user;
    }
    public function reload($totalCourses,$pubCourses,$draftCourses,$pubLessons,$draftLessons,$inProgress,$completed,$totalUsers)
    {

         $this->totalCourses= $totalCourses;
         $this->pubCourses = $pubCourses;
         $this->draftCourses = $draftCourses;
         $this->pubLessons= $pubLessons;
         $this->draftLessons=$draftLessons;
         $this->inProgress = $inProgress;
         $this->completed = $completed;
         $this->totalUsers = $totalUsers;
    }




};
?>

<div>
    <div
        style="font-size:10px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">
        Platform overview
    </div>
    <div style="display:flex;flex-direction:column;gap:3px;">
        @placeholder
        <div style="display:flex;flex-direction:column;gap:3px;">
            @foreach(range(1, 4) as $index)
                <div style="display:flex;justify-content:space-between;padding:4px 6px;">
                    <div style="width: 50px; height: 12px; background: #e5e7eb; border-radius: 4px; animation: pulse 2s infinite;"></div>
                    <div style="width: 30px; height: 12px; background: #e5e7eb; border-radius: 4px; animation: pulse 2s infinite;"></div>
                </div>
            @endforeach
        </div>
        @endplaceholder

        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
            <span>Users</span><span style="font-weight:500;color:var(--text);">{{ $totalUsers }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
            <span>Courses</span><span style="font-weight:500;color:var(--text);">{{ $totalCourses }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
            <span>Published</span><span style="font-weight:500;color:#065f46;">{{ $pubLessons }}lessons</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
            <span>Drafts</span><span style="font-weight:500;color:#6b7280;">{{ $draftLessons }}lessons</span>
        </div>
    </div>
</div>
