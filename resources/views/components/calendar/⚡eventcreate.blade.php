<?php

use App\Models\event;
use Livewire\Component;

new class extends Component {
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

    public function bruh()
    {
        dd('bruh');
    }

};
?>

<div  x-data="{event_create_modal_open : false, event_update_modal_open :false}">
    <div class="cal-sidebar">
        <button class="cal-add-btn" id="cal-add-btn" @click="event_create_modal_open = true">+ Add event</button>

        <div>
            <div class="cal-sb-label">Event types</div>
            <div style="display:flex;flex-direction:column;gap:2px;margin-top:4px;">
                @foreach(['exam','vacation','project','assignment','personal'] as $type)
                    <div class="type-filter" data-type="{{ $type }}">
                        <span class="type-dot dot-{{ $type }}"></span>
                        {{ ucfirst($type) }}
                    </div>
                @endforeach
            </div>
        </div>

        <div style="height:1px;background:var(--border);"></div>

        <div>
            <div class="cal-sb-label">Upcoming</div>
            <div id="upcoming-list" style="display:flex;flex-direction:column;gap:6px;margin-top:4px;"></div>
        </div>
    </div>

    <div class="cal-modal-backdrop modal" id="cal-modal" x-show="event_create_modal_open">
        <div class="cal-modal" @click.away="event_create_modal_open = false">
            <button class="cal-modal-close" id="cal-modal-close" @click="event_create_modal_open = false">✕</button>
            <h3 id="modal-title">New event</h3>

            <div class="cal-form-group">
                <label>Title</label>
                <input type="text" id="f-title" placeholder="Event title" wire:model="title">
            </div>
            <div class="cal-form-group">
                <label>Description</label>
                <textarea id="f-desc" rows="2" placeholder="Optional description" wire:model="description"></textarea>
            </div>
            <div class="cal-form-row">
                <div class="cal-form-group">
                    <label>Start date</label>
                    <input type="date" id="f-start" wire:model="start_date">
                </div>
                <div class="cal-form-group">
                    <label>End date</label>
                    <input type="date" id="f-end" wire:model="end_date">
                </div>
            </div>
            <div class="cal-form-group">
                <label>Type</label>
                <select id="f-type" wire:model="type">
                    <option value="exam">Exam</option>
                    <option value="vacation">Vacation</option>
                    <option value="project">Project</option>
                    <option value="assignment">Assignment</option>
                    <option value="personal">Personal</option>
                </select>
            </div>

            {{-- Admin only: visibility --}}
            <div class="cal-form-group" id="vis-row">
                <label>Visibility</label>
                <div class="visibility-row">
                    <input type="hidden" id="f-vis" value="global" wire:model="visibility" wire:model="visibility">
                    <button type="button" class="vis-opt selected" data-val="global"
                            wire:click="$set('visibility','global')">🌍 Global (all users)
                    </button>
                    <button type="button" class="vis-opt" data-val="personal"
                            wire:click="$set('visibility','personal')">🔒 Personal (only me)
                    </button>
                </div>

            </div>

            <div class="cal-modal-actions">
                <button class="cal-btn-delete" id="cal-delete-btn"
                >Delete
                </button>
                <button class="cal-btn-cancel" id="cal-cancel-btn"
                        {{--@click="event_create_modal_open = false"--}} wire:click="bruh">Cancel
                </button>
                <button type="submit" class="cal-btn-submit" id="cal-submit-btn" wire:click="store">Save event</button>
            </div>
        </div>
    </div>
</div>

