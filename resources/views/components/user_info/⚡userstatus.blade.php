<?php

use App\Models\user;
use Livewire\Component;

new class extends Component {
    public $userId;
    public $user;
    public $userStatus;



    public function mount($user)
    {
        $this->user = $user;
        $this->updatestatus();



    }

    public function updatestatus(){
        $this->user = user::findOrFail($this->user->id);
        $this->userStatus= $this->user->last_seen;
        $this->dispatch('statusUpdated',last_seen:$this->user->last_seen);

    }
};
?>

<div wire:poll.5s="updatestatus()">
    @if($user->last_seen && $userStatus->gt(now()->subSeconds(50)))
        <span style="color:green;">Online</span>
    @else
        <span style="color:red;">Offline</span>
    @endif
</div>
