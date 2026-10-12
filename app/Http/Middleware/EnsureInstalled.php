<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installing = $request->is('install', 'install/*');

        if (! is_installed() && ! $installing) {
            return redirect()->route('install.welcome');
        }
        if (is_installed() && $installing) {
            abort(404);
        }

        return $next($request);
    }
}
