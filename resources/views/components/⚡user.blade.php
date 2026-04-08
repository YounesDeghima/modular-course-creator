<?php

use Livewire\Component;

new class extends Component
{
    public $user;
    public function mount($user)
    {
        $this->user = $user;
    }
    public function goToProfile($userId){
        return redirect()->route('admin.userProfile',$userId);
    }

};
?>

<tr data-id="{{ $user->id }}"
    data-name="{{ strtolower($user->name . ' ' . $user->last_name) }}"
    data-email="{{ strtolower($user->email) }}"
    data-role="{{ $user->role }}"
    data-joined="{{ $user->created_at->timestamp }}"
    wire:click="goToProfile({{$user->id}})">
    <td>
        <div class="user-avatar">
            {{ strtoupper(substr($user->name,0,1)) }}{{ strtoupper(substr($user->last_name,0,1)) }}
        </div>
    </td>
    <td>{{ $user->name }} {{ $user->last_name }}</td>
    <td style="color:var(--text-muted);">{{ $user->email }}</td>
    <td>
                    <span class="role-badge {{ $user->role === 'admin' ? 'role-admin' : 'role-user' }}">
                        {{ $user->role === 'admin' ? 'Admin' : 'Student' }}
                    </span>
    </td>
    <td style="color:var(--text-muted);font-size:12px;">
        {{ $user->created_at->format('M d, Y') }}
    </td>
    <td>
        <div style="display:flex;gap:5px;">
            <button class="action-btn edit-btn"
                    data-id="{{ $user->id }}"
                    data-name="{{ $user->name }}"
                    data-last="{{ $user->last_name }}"
                    data-email="{{ $user->email }}"
                    data-role="{{ $user->role }}">
                Edit
            </button>
            @if($user->id)
                <button class="action-btn danger delete-btn"
                        data-id="{{ $user->id }}">
                    Delete
                </button>
            @endif
        </div>
    </td>
</tr>
