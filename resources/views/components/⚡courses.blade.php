<?php

use App\Models\course;
use Livewire\Component;

new class extends Component {
    public $courses;
    protected $listeners = ['courseCreated' => 'addCourse'];

    public function mount($courses)
    {
        $this->courses = $courses;

    }

    public function addCourse(int $id){
        $course = course::find($id);

        if($course){
            $this->courses->prepend($course);
        }

    }
};
?>

<div class="blocks"
     id="blocks-container"
{{--     x-data="{courses:'{{$courses}}'}"--}}
{{--     @courseCreated="courses=$event.detail[0].$course;--}}
{{--     console.log($event.detail);"--}}
     wire:poll.5s >

    @foreach($courses as $course)

        <livewire:courseupdate :course="$course" :key="$course->id"/>
    @endforeach
</div>
