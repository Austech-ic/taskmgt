<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifiedIfRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && setting('require_email_verification') && ! $user->email_verified_at && ! $user->isStaff()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
