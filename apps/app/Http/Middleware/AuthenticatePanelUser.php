<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\FilamentAuthRedirect;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Filament's Authenticate, but a signed-in user who may not enter the panel
 * is sent where they belong (home, or the verification prompt) instead of
 * getting a 403 page.
 *
 * RedirectIfNotPanelUser cannot do this on its own: Authenticate is in
 * Laravel's middleware priority list, so it always runs first and aborts.
 */
class AuthenticatePanelUser extends Authenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $user = Filament::auth()->user();

        if (($user instanceof User) && (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))) {
            throw new HttpResponseException(redirect(FilamentAuthRedirect::for($user)));
        }

        parent::authenticate($request, $guards);
    }
}
