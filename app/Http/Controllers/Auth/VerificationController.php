<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function notice(Request $request)
    {
        if ($request->user()->email_verified_at) {
            return redirect()->route('app.dashboard');
        }

        return view('auth.verify');
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        abort_unless($request->user()->id === $user->id && hash_equals(sha1($user->email), $hash), 403);
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect()->route('app.dashboard')->with('success', 'Email verified ✅');
    }

    public function resend(Request $request)
    {
        try {
            $request->user()->notifyNow(new VerifyEmail);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'We couldn’t send the email right now. Try again in a bit.');
        }

        return back()->with('success', 'Fresh link sent — check your inbox (and spam) 📬');
    }
}
