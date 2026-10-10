<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a signed-in account that is not verified off the public site: every
 * page sends it to the verification notice, as the `verified` middleware does
 * for the account pages. That prompt either waits for the emailed link or,
 * with auth.registration_approval on, signs the account out until a
 * super_admin approves it.
 *
 * Only page loads are redirected. Livewire's update endpoint also runs in the
 * web group, and the verification prompt needs it to resend the link.
 */
class RedirectUnverifiedUser
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ($user instanceof MustVerifyEmail)
            && (! $user->hasVerifiedEmail())
            && $request->isMethod('GET')
            && (! $request->expectsJson())
            && (! $request->routeIs('verification.notice'))
        ) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
