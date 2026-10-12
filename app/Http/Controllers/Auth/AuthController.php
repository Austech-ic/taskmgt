<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegister(Request $request)
    {
        abort_unless(setting('allow_registration'), 403, 'Registration is closed right now.');

        return view('auth.register', ['as' => $request->query('as') === 'hirer' ? 'hirer' : 'talent']);
    }

    public function register(Request $request)
    {
        abort_unless(setting('allow_registration'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'as' => 'required|in:talent,hirer',
            'terms' => 'accepted',
        ], ['terms.accepted' => 'Please accept the terms to continue.']);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($user));
        if (setting('require_email_verification')) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }
        }
        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('auth.registered', $user);

        return redirect()->route('app.onboarding', ['want' => $data['as']]);
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (! Auth::attempt(['email' => strtolower($credentials['email']), 'password' => $credentials['password']], $request->boolean('remember'))) {
            AuditLog::record('auth.failed', null, ['email' => $credentials['email']]);

            return back()->withInput($request->only('email'))->withErrors(['email' => 'Those details don’t match our records.']);
        }

        $request->session()->regenerate();
        $user = $request->user();
        if ($user->isSuspended()) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account is suspended. Contact support if you think that’s a mistake.']);
        }

        return redirect()->intended($user->isStaff() && ! $user->is_talent && ! $user->is_hirer ? route('admin.dashboard') : route('app.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Logged out. Catch you later ✌️');
    }
}
