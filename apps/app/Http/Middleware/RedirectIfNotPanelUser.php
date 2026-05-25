<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotPanelUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($request->routeIs([
            'filament.*.auth.*',
            'filament-developer-logins.login-as',
        ])) {
            return $next($request);
        }

        if ($user && ! ($user->hasRole('super_admin') || $user->hasRole('scientist'))) {
            return redirect('/');
        }

        return $next($request);
    }
}
