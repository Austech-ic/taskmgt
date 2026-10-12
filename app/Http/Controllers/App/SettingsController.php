<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('app.settings', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:40',
            'email_notifications' => 'nullable|boolean',
        ]);
        if (strtolower($data['email']) !== $user->email) {
            $user->email_verified_at = null;
        }
        $user->fill(['name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null]);
        $user->notification_prefs = ['email' => $request->boolean('email_notifications')];
        $user->save();

        return back()->with('success', 'Settings saved.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();

        return back()->with('success', 'Password updated 🔐');
    }
}
