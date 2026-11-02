<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display profile page.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Update basic information
        $user->fill(
            $request->safe()->only([
                'name',
                'email',
                'bio',
                'phone',
            ])
        );

        // Reset email verification if email changes
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Remove existing profile picture
        if ($request->boolean('remove_profile_photo')) {

            if ($user->profile_photo_path) {
                Storage::disk('public')->delete(
                    $user->profile_photo_path
                );
            }

            $user->profile_photo_path = null;
        }

        // Upload new profile picture
        elseif ($request->hasFile('profile_photo')) {

            // Delete old picture
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete(
                    $user->profile_photo_path
                );
            }

            // Save new picture
            $path = $request->file('profile_photo')
                ->store('profile-photos', 'public');

            $user->profile_photo_path = $path;
        }

        $user->save();

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated');
    }

    /**
     * Delete account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Delete profile picture
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete(
                $user->profile_photo_path
            );
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}