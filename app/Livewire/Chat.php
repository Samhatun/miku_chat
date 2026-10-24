<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chat extends Component
{
    public $selectedUserId = null;
    public $newMessage = '';
    public $search = '';

    public function selectUser($userId): void
    {
        $user = User::findOrFail($userId);

        abort_if((int) $user->id === (int) Auth::id(), 404);

        $this->selectedUserId = $user->id;
        $this->reset('newMessage');

        ChatMessage::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function sendMessage(): void
    {
        $this->validate([
            'newMessage' => ['required', 'string', 'max:5000'],
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        abort_if((int) $this->selectedUserId === (int) Auth::id(), 403);

        ChatMessage::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->selectedUserId,
            'message' => $this->newMessage,
        ]);

        $this->reset('newMessage');
        $this->resetValidation('newMessage');
    }

    public function render()
    {
        $users = User::query()
            ->where('id', '!=', Auth::id())
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();

        $messages = collect();

        if ($this->selectedUserId) {
            $messages = ChatMessage::query()
                ->where(function ($query) {
                    $query->where('sender_id', Auth::id())
                        ->where('receiver_id', $this->selectedUserId);
                })
                ->orWhere(function ($query) {
                    $query->where('sender_id', $this->selectedUserId)
                        ->where('receiver_id', Auth::id());
                })
                ->orderBy('created_at')
                ->get();
        }

        return view('livewire.chat', [
            'users' => $users,
            'messages' => $messages,
        ]);
    }
}
