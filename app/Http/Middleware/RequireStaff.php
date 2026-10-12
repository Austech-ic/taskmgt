<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStaff
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isStaff()) {
            abort(403);
        }
        if ($roles && ! $user->hasStaffRole(...$roles)) {
            abort(403, 'Your staff role doesn’t include this area.');
        }

        return $next($request);
    }
}
