<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->organization()) {
            return redirect()->route('app.company.edit')->with('info', 'Set up your company profile first — takes 60 seconds.');
        }

        return $next($request);
    }
}
