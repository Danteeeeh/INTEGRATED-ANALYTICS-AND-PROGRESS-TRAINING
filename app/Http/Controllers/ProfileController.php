<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('role');
        $preferences = UserPreference::query()
            ->where('user_id', $user->id)
            ->whereIn('key', ['email_notifications', 'in_app_notifications', 'weekly_summary'])
            ->pluck('value', 'key');

        return view('profile.edit', compact('user', 'preferences'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        if ($request->boolean('remove_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $validated['profile_photo_path'] = null;
        }
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }
        unset($validated['profile_photo'], $validated['remove_photo']);
        $user->update($validated);

        return back()->with('status', 'Profile information updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('status', 'Password updated successfully.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_notifications' => ['nullable', 'boolean'],
            'in_app_notifications' => ['nullable', 'boolean'],
            'weekly_summary' => ['nullable', 'boolean'],
        ]);

        foreach (['email_notifications', 'in_app_notifications', 'weekly_summary'] as $key) {
            UserPreference::updateOrCreate(
                ['user_id' => $request->user()->id, 'key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0']
            );
        }

        return back()->with('status', 'Notification preferences updated successfully.');
    }
}
