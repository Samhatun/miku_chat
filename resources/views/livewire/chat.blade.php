<div class="flex h-[80vh] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">

    {{-- Contacts sidebar --}}
    <div class="flex w-1/3 min-w-[220px] flex-col border-r border-gray-200">
        <div class="border-b bg-green-600 p-4 text-white">
            <h1 class="text-xl font-bold">Miku Chat</h1>
            <p class="text-sm">Your messages, your people</p>
        </div>

        <div class="p-3">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Search contacts..."
                class="w-full rounded-full border-gray-300 text-sm focus:border-green-500 focus:ring-green-500"
            >
        </div>

        <div class="flex-1 overflow-y-auto">
            @forelse ($users as $user)
                <button
                    type="button"
                    wire:click="selectUser({{ $user->id }})"
                    class="flex w-full items-center gap-3 border-b p-4 text-left transition hover:bg-green-50 {{ $selectedUserId == $user->id ? 'bg-green-100' : '' }}"
                >
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-green-600 font-bold text-white">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>

                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-800">
                            {{ $user->name }}
                        </p>
                        <p class="truncate text-xs text-gray-500">
                            {{ $user->email }}
                        </p>
                    </div>
                </button>
            @empty
                <p class="p-4 text-sm text-gray-500">
                    No other users found. Register a second account to start chatting.
                </p>
            @endforelse
        </div>
    </div>

    {{-- Conversation area --}}
    <div class="flex min-w-0 flex-1 flex-col bg-gray-50">
        @if ($selectedUserId)
            @php
                $activeUser = $users->firstWhere('id', $selectedUserId)
                    ?? \App\Models\User::find($selectedUserId);
            @endphp

            <div class="flex items-center gap-3 border-b bg-white p-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 font-bold text-white">
                    {{ strtoupper(substr($activeUser?->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <h2 class="font-semibold text-gray-800">
                        {{ $activeUser?->name ?? 'Conversation' }}
                    </h2>
                    <p class="text-xs text-gray-500">Miku Chat</p>
                </div>
            </div>

            <div
                class="flex-1 space-y-3 overflow-y-auto p-4"
                wire:poll.3s
            >
                @forelse ($messages as $message)
                    <div class="flex {{ $message->sender_id == auth()->id() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-4 py-2 shadow-sm {{ $message->sender_id == auth()->id() ? 'rounded-br-sm bg-green-200 text-gray-800' : 'rounded-bl-sm bg-white text-gray-800' }}">
                            <p class="whitespace-pre-wrap break-words">{{ $message->message }}</p>
                            <p class="mt-1 text-right text-[10px] text-gray-500">
                                {{ $message->created_at->format('g:i A') }}
                                @if ($message->sender_id == auth()->id() && $message->read_at)
                                    ✓✓
                                @elseif ($message->sender_id == auth()->id())
                                    ✓
                                @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center text-center text-sm text-gray-500">
                        No messages yet. Say hello!
                    </div>
                @endforelse
            </div>

            <form wire:submit="sendMessage" class="flex items-center gap-3 border-t bg-white p-3">
                <input
                    type="text"
                    wire:model="newMessage"
                    placeholder="Type a message..."
                    autocomplete="off"
                    class="flex-1 rounded-full border-gray-300 focus:border-green-500 focus:ring-green-500"
                >

                <button
                    type="submit"
                    class="rounded-full bg-green-600 px-5 py-2 font-semibold text-white hover:bg-green-700"
                >
                    Send
                </button>
            </form>

            @error('newMessage')
                <p class="bg-white px-4 pb-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        @else
            <div class="flex flex-1 flex-col items-center justify-center p-8 text-center">
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-4xl">
                    💬
                </div>
                <h2 class="text-2xl font-semibold text-gray-700">Welcome to Miku Chat</h2>
                <p class="mt-2 text-gray-500">Select a contact to start a conversation.</p>
            </div>
        @endif
    </div>
</div>
