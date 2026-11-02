<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">My Profile</h2>
    </x-slot>

    <div class="min-h-screen bg-gray-100 py-10">
        <div class="mx-auto max-w-4xl space-y-6 px-4">

            {{-- PROFILE INFORMATION --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow">

                <div class="bg-green-600 p-6 text-white">
                    <h1 class="text-2xl font-bold">Profile Settings</h1>
                    <p class="mt-1 text-sm text-green-100">Manage your Miku Chat profile</p>
                </div>

                <div class="p-6 sm:p-8">

                    @if (session('status') === 'profile-updated')
                        <div class="mb-5 rounded-lg bg-green-100 p-3 text-sm text-green-700">
                            Profile updated successfully!
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('patch')

                        {{-- PROFILE PHOTO --}}
                        <div class="mb-8 flex flex-col items-center">

                            <x-avatar :user="$user" size="h-32 w-32" />

                            <h2 class="mt-4 text-xl font-bold text-gray-800">{{ $user->name }}</h2>
                            <p class="text-sm text-gray-500">{{ $user->email }}</p>

                            <label class="mt-4 cursor-pointer rounded-full bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                Change Profile Picture
                                <input
                                    type="file"
                                    name="profile_photo"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    onchange="this.form.requestSubmit()"
                                >
                            </label>

                            @error('profile_photo')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            @if ($user->profile_photo_path)
                                <label class="mt-3 flex items-center gap-2 text-sm text-red-500">
                                    <input type="checkbox" name="remove_profile_photo" value="1">
                                    Remove profile picture (then click Save Changes)
                                </label>
                            @endif

                        </div>

                        <hr class="mb-6">

                        {{-- NAME --}}
                        <div class="mb-5">
                            <label class="mb-2 block font-medium text-gray-700">Display Name</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                required
                                class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- EMAIL --}}
                        <div class="mb-5">
                            <label class="mb-2 block font-medium text-gray-700">Email Address</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                required
                                class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- PHONE --}}
                        <div class="mb-5">
                            <label class="mb-2 block font-medium text-gray-700">Phone Number</label>
                            <input
                                type="text"
                                name="phone"
                                value="{{ old('phone', $user->phone) }}"
                                placeholder="Enter your phone number"
                                class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500"
                            >
                            @error('phone')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- BIO --}}
                        <div class="mb-6">
                            <label class="mb-2 block font-medium text-gray-700">About / Bio</label>
                            <textarea
                                name="bio"
                                rows="4"
                                maxlength="500"
                                placeholder="Tell people something about yourself..."
                                class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500"
                            >{{ old('bio', $user->bio) }}</textarea>
                            @error('bio')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">Maximum 500 characters.</p>
                        </div>

                        {{-- SAVE BUTTON --}}
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-green-600 px-8 py-3 font-semibold text-white hover:bg-green-700">
                                Save Changes
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            {{-- PASSWORD SETTINGS --}}
            <div class="rounded-2xl bg-white p-6 shadow sm:p-8">
                @include('profile.partials.update-password-form')
            </div>

            {{-- DELETE ACCOUNT --}}
            <div class="rounded-2xl bg-white p-6 shadow sm:p-8">
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>

</x-app-layout>