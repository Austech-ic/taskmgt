<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Gates the talent / hirer workspaces and keeps the session's active workspace in sync. */
class RequireWorkspace
{
    public function handle(Request $request, Closure $next, string $workspace): Response
    {
        $user = $request->user();
        $allowed = $workspace === 'talent' ? $user->is_talent : $user->is_hirer;

        if (! $allowed) {
            return redirect()->route('app.onboarding', ['want' => $workspace])
                ->with('info', $workspace === 'talent' ? 'Turn on your talent workspace to use that.' : 'Turn on your hiring workspace to use that.');
        }
        if ($workspace === 'talent' && ! $user->profile) {
            return redirect()->route('app.onboarding', ['want' => 'talent']);
        }
        session(['workspace' => $workspace]);

        return $next($request);
    }
}
