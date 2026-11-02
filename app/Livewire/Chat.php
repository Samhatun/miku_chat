<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chat extends Component
{
    public $selectedUserId = null;
    public $newMessage = '';
    public $search = '';
    public $tab = 'friends'; // friends | find | requests

    /* ---------- Friends ---------- */

    protected function areFriends(int $userId): bool
    {
        return Friendship::between(Auth::id(), $userId)
            ->where('status', 'accepted')
            ->exists();
    }

    public function sendRequest($userId): void
    {
        $userId = (int) $userId;

        abort_if($userId === (int) Auth::id(), 403);
        User::findOrFail($userId);

        $existing = Friendship::between(Auth::id(), $userId)->first();

        if ($existing) {
            // They already sent us a request, so accept it instead of duplicating
            if ($existing->status === 'pending' && (int) $existing->receiver_id === (int) Auth::id()) {
                $existing->update(['status' => 'accepted']);
            }
            return;
        }

        Friendship::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $userId,
            'status' => 'pending',
        ]);
    }

    public function acceptRequest($friendshipId): void
    {
        Friendship::where('id', $friendshipId)
            ->where('receiver_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail()
            ->update(['status' => 'accepted']);
    }

    /** Used for both "decline" (receiver) and "cancel" (sender). */
    public function removeRequest($friendshipId): void
    {
        Friendship::where('id', $friendshipId)
            ->where('status', 'pending')
            ->where(fn ($q) => $q->where('sender_id', Auth::id())->orWhere('receiver_id', Auth::id()))
            ->delete();
    }

    public function unfriend($userId): void
    {
        Friendship::between(Auth::id(), (int) $userId)
            ->where('status', 'accepted')
            ->delete();

        if ((int) $this->selectedUserId === (int) $userId) {
            $this->selectedUserId = null;
        }
    }

    /* ---------- Chat ---------- */

    public function selectUser($userId): void
    {
        $user = User::findOrFail($userId);

        abort_if((int) $user->id === (int) Auth::id(), 404);
        abort_unless($this->areFriends($user->id), 403);

        $this->selectedUserId = $user->id;
        $this->reset('newMessage');
        $this->resetValidation();

        ChatMessage::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function sendMessage(): void
    {
        $this->newMessage = trim($this->newMessage);

        $this->validate([
            'newMessage' => ['required', 'string', 'max:5000'],
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        abort_if((int) $this->selectedUserId === (int) Auth::id(), 403);
        abort_unless($this->areFriends((int) $this->selectedUserId), 403);

        ChatMessage::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->selectedUserId,
            'message' => $this->newMessage,
        ]);

        $this->reset('newMessage');
        $this->resetValidation();
        $this->dispatch('message-sent');
    }

    /* ---------- Render ---------- */

    public function render()
    {
        $me = Auth::id();

        $friendships = Friendship::where(fn ($q) => $q->where('sender_id', $me)->orWhere('receiver_id', $me))->get();

        $friendIds = $friendships
            ->where('status', 'accepted')
            ->map(fn ($f) => (int) $f->sender_id === (int) $me ? $f->receiver_id : $f->sender_id)
            ->values();

        $incoming = $friendships
            ->where('status', 'pending')
            ->where('receiver_id', $me)
            ->keyBy('sender_id');

        $outgoing = $friendships
            ->where('status', 'pending')
            ->where('sender_id', $me)
            ->keyBy('receiver_id');

        $search = $this->search;
        $matchesSearch = function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        };

        $users = collect();
        $requests = collect();

        if ($this->tab === 'friends') {
            $users = User::whereIn('id', $friendIds)
                ->where($matchesSearch)
                ->orderBy('name')
                ->get();

            foreach ($users as $user) {
                $user->latestMessage = ChatMessage::query()
                    ->where(function ($q) use ($user, $me) {
                        $q->where(fn ($q) => $q->where('sender_id', $me)->where('receiver_id', $user->id))
                          ->orWhere(fn ($q) => $q->where('sender_id', $user->id)->where('receiver_id', $me));
                    })
                    ->latest('created_at')
                    ->first();
            }
        } elseif ($this->tab === 'find' && strlen(trim($search)) >= 2) {
            $users = User::where('id', '!=', $me)
                ->whereNotIn('id', $friendIds)
                ->where($matchesSearch)
                ->orderBy('name')
                ->limit(20)
                ->get();
        } elseif ($this->tab === 'requests') {
            $requests = Friendship::with('sender')
                ->where('receiver_id', $me)
                ->where('status', 'pending')
                ->latest()
                ->get();
        }

        $messages = collect();
        $activeUser = null;

        if ($this->selectedUserId && $this->areFriends((int) $this->selectedUserId)) {
            $activeUser = User::find($this->selectedUserId);

            $messages = ChatMessage::query()
                ->where(function ($q) use ($me) {
                    $q->where(fn ($q) => $q->where('sender_id', $me)->where('receiver_id', $this->selectedUserId))
                      ->orWhere(fn ($q) => $q->where('sender_id', $this->selectedUserId)->where('receiver_id', $me));
                })
                ->orderBy('created_at')
                ->get();
        }

        return view('livewire.chat', [
            'users' => $users,
            'requests' => $requests,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'incomingCount' => $incoming->count(),
            'messages' => $messages,
            'activeUser' => $activeUser,
        ]);
    }
}