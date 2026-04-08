<?php

use App\Models\course;
use Livewire\Component;

new class extends Component {

    public $total;
    public $published;
    public $draft;

    public function mount()
    {
        $this->updateCount();
    }

    public function updateCount()
    {
        $this->total = course::count();
        $this->published = course::where('status', 'published')->count();
        $this->draft = course::where('status', 'draft')->count();

    }
};
?>

<div class="admin-sb-section" wire:poll.1s="updateCount()">
    <div class="admin-sb-label">Overview</div>
    <div class="admin-stat-row">
        <span>Total</span>
        <span class="admin-stat-val" id="total-courses">{{ $total }}</span>
    </div>
    <div class="admin-stat-row">
        <span>Published</span>
        <span class="admin-stat-val" id="published-count" style="color:#065f46">
            {{ $published }}
        </span>
    </div>
    <div class="admin-stat-row">
        <span>Draft</span>
        <span class="admin-stat-val" id="draft-count" style="color:#6b7280">
            {{ $draft }}
        </span>
    </div>
</div>
