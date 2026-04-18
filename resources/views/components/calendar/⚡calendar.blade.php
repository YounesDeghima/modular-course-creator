<?php

use App\Models\event;
use Livewire\Component;

new class extends Component {
    public $user;
    public $title;
    public $description;
    public $start_date;
    public $end_date;
    public $type;
    public $visibility;
    public $user_id;
    public $event;


    public function mount()
    {

        $this->user = Auth::user();
        $this->user_id = $this->user->id;


    }

    public function store()
    {
        dd('store');

        $validated = $this->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'type' => 'required|in:exam,vacation,project,assignment,personal',
            'visibility' => 'required|in:global,personal',
        ]);


        $this->event = event::create($validated);
        dd('created');

    }
    public function bruh(){
        dd('bruh');
    }

};
?>

<div>
    <div class="cal-page">
        <div class="cal-header">
            <div class="cal-nav">
                <button class="cal-nav-btn" id="cal-prev">‹</button>
                <span class="cal-month-title" id="cal-month-title"></span>
                <button class="cal-nav-btn" id="cal-next">›</button>
            </div>
            <div class="view-toggle">
                <button class="view-btn active" data-view="month">Month</button>
                <button class="view-btn" data-view="week">Week</button>
                <button class="view-btn" data-view="agenda">Agenda</button>
            </div>
        </div>

        <div class="month-view" id="month-view">
            <div class="dow-headers">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                    <div class="dow-hdr">{{ $d }}</div>
                @endforeach
            </div>
            <div class="days-grid" id="days-grid"></div>
        </div>

        <div class="week-view" id="week-view">
            <div id="week-grid"></div>
        </div>

        <div class="agenda-view" id="agenda-view">
            <div id="agenda-list"></div>
        </div>
    </div>

    {{-- Event modal — admin has visibility toggle --}}

</div>
