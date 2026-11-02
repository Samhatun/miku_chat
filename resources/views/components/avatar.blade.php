@props(['user', 'size' => 'h-11 w-11'])

@if ($user->profile_photo_path)
    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
         class="{{ $size }} shrink-0 rounded-full object-cover">
@else
    <div class="{{ $size }} flex shrink-0 items-center justify-center rounded-full bg-green-600 font-bold text-white">
        {{ strtoupper(substr($user->name, 0, 1)) }}
    </div>
@endif