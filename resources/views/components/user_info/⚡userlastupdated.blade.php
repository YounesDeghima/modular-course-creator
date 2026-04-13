<?php


use Carbon\Carbon;
use Livewire\Component;

new class extends Component {
    public $user;
    public $timeAgo;

    protected $listeners = ['statusUpdated' => 'updatelastseen'];

    public function mount($user)
    {
        $this->user = $user;

    }

    public function updatelastseen($last_seen)
    {


        $this->user->last_seen = $last_seen;
        $this->updateTimeAgo();
    }

    public function updateTimeAgo()
    {
        if ($this->user->last_seen) {
            $this->timeAgo = carbon::parse($this->user->last_seen)->diffForHumans();
        } else {
            $this->timeAgo = 'never';
        }
    }
};
?>

<div class="info-row" wire:poll.5s>
    <span class="info-key">Last updated</span>
    <span class="info-val">{{ $timeAgo }}</span>
</div>
