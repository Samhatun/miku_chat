<div class="flex h-[80vh] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">

    {{-- Sidebar --}}
    <div class="flex w-1/3 min-w-[240px] flex-col border-r border-gray-200">
        <div class="border-b bg-green-600 p-4 text-white">
            <h1 class="text-xl font-bold">Miku Chat</h1>
            <p class="text-sm">Your messages, your people</p>
        </div>

        {{-- Tabs --}}
        <div class="flex border-b text-sm font-medium">
            @foreach (['friends' => 'Friends', 'find' => 'Find people', 'requests' => 'Requests'] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    class="relative flex-1 py-3 {{ $tab === $key ? 'border-b-2 border-green-600 text-green-700' : 'text-gray-500 hover:bg-gray-50' }}">
                    {{ $label }}
                    @if ($key === 'requests' && $incomingCount > 0)
                        <span class="ml-1 rounded-full bg-red-500 px-1.5 text-xs text-white">{{ $incomingCount }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        @if ($tab !== 'requests')
            <div class="p-3">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="{{ $tab === 'find' ? 'Search by name or email...' : 'Search friends...' }}"
                    class="w-full rounded-full border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
            </div>
        @endif

        <div class="flex-1 overflow-y-auto">

            {{-- FRIENDS --}}
            @if ($tab === 'friends')
                @forelse ($users as $user)
                    <div wire:key="friend-{{ $user->id }}"
                         class="flex items-center border-b hover:bg-green-50 {{ $selectedUserId == $user->id ? 'bg-green-100' : '' }}">
                        <button type="button" wire:click="selectUser({{ $user->id }})"
                                class="flex min-w-0 flex-1 items-center gap-3 p-4 text-left">

                            <x-avatar :user="$user" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-gray-800">{{ $user->name }}</p>
                                @if ($user->latestMessage)
                                    <p class="truncate text-sm text-gray-500">
                                        {{ $user->latestMessage->sender_id == auth()->id() ? 'You: ' : '' }}{{ $user->latestMessage->message }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $user->latestMessage->created_at->isToday()
                                            ? $user->latestMessage->created_at->format('g:i A')
                                            : $user->latestMessage->created_at->format('M j') }}
                                    </p>
                                @else
                                    <p class="truncate text-xs text-gray-500">Say hello 👋</p>
                                @endif
                            </div>
                        </button>
                    </div>
                @empty
                    <p class="p-4 text-sm text-gray-500">
                        No friends yet. Use <strong>Find people</strong> to send a request.
                    </p>
                @endforelse
            @endif

            {{-- FIND PEOPLE --}}
            @if ($tab === 'find')
                @if (strlen(trim($search)) < 2)
                    <p class="p-4 text-sm text-gray-500">Type at least 2 characters to search for people.</p>
                @else
                    @forelse ($users as $user)
                        <div wire:key="find-{{ $user->id }}" class="flex items-center gap-3 border-b p-4">

                            <x-avatar :user="$user" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-gray-800">{{ $user->name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $user->email }}</p>
                            </div>

                            @if ($incoming->has($user->id))
                                <button wire:click="acceptRequest({{ $incoming[$user->id]->id }})"
                                    class="rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700">
                                    Accept
                                </button>
                            @elseif ($outgoing->has($user->id))
                                <button wire:click="removeRequest({{ $outgoing[$user->id]->id }})"
                                    class="rounded-full border border-gray-300 px-3 py-1 text-xs text-gray-600 hover:bg-gray-100">
                                    Requested ✕
                                </button>
                            @else
                                <button wire:click="sendRequest({{ $user->id }})"
                                    class="rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700">
                                    Add
                                </button>
                            @endif
                        </div>
                    @empty
                        <p class="p-4 text-sm text-gray-500">No users found.</p>
                    @endforelse
                @endif
            @endif

            {{-- REQUESTS --}}
            @if ($tab === 'requests')
                @forelse ($requests as $request)
                    <div wire:key="req-{{ $request->id }}" class="flex items-center gap-3 border-b p-4">

                        <x-avatar :user="$request->sender" />

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-800">{{ $request->sender->name }}</p>
                            <p class="text-xs text-gray-500">wants to be your friend</p>
                        </div>
                        <button wire:click="acceptRequest({{ $request->id }})"
                            class="rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700">
                            Accept
                        </button>
                        <button wire:click="removeRequest({{ $request->id }})"
                            class="rounded-full border border-gray-300 px-3 py-1 text-xs text-gray-600 hover:bg-gray-100">
                            Decline
                        </button>
                    </div>
                @empty
                    <p class="p-4 text-sm text-gray-500">No pending requests.</p>
                @endforelse
            @endif
        </div>
    </div>

    {{-- Conversation area --}}
    <div class="flex min-w-0 flex-1 flex-col bg-gray-50">
        @if ($activeUser)

            {{-- Header --}}
            <div class="flex items-center gap-3 border-b bg-white p-4">
                <x-avatar :user="$activeUser" size="h-10 w-10" />
                <div class="flex-1">
                    <h2 class="font-semibold text-gray-800">{{ $activeUser->name }}</h2>
                    <p class="text-xs text-gray-500">Miku Chat</p>
                </div>
                <button type="button"
                    wire:click="unfriend({{ $activeUser->id }})"
                    wire:confirm="Remove {{ $activeUser->name }} from your friends?"
                    class="text-xs text-gray-400 hover:text-red-500">
                    Unfriend
                </button>
            </div>

            {{-- Messages --}}
            <div class="flex-1 space-y-3 overflow-y-auto p-4" wire:poll.3s>
                @forelse ($messages as $message)
                    <div
                        wire:key="message-{{ $message->id }}"
                        class="flex {{ $message->sender_id == auth()->id() ? 'justify-end' : 'justify-start' }}"
                    >
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

            {{-- Message form --}}
            <form
                wire:submit="sendMessage"
                x-data
                x-on:message-sent.window="$refs.messageInput.value = ''; $refs.messageInput.focus()"
                class="flex items-center gap-3 border-t bg-white p-3"
            >
                <input
                    type="text"
                    x-ref="messageInput"
                    wire:model="newMessage"
                    placeholder="Type a message..."
                    autocomplete="off"
                    class="min-w-0 flex-1 rounded-full border-gray-300 focus:border-green-500 focus:ring-green-500"
                />

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="shrink-0 rounded-full bg-green-600 px-5 py-2 font-semibold text-white hover:bg-green-700 disabled:opacity-50"
                >
                    Send
                </button>
            </form>

        @else
            <div class="flex flex-1 flex-col items-center justify-center p-8 text-center">
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-4xl">
                    💬
                </div>

                <h2 class="text-2xl font-semibold text-gray-700">Welcome to Miku Chat</h2>

                <p class="mt-2 text-gray-500">Select a friend to start a conversation.</p>
            </div>
        @endif
    </div>
</div>