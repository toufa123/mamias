<?php

declare(strict_types=1);

namespace App\Filament\Auth\Concerns;

use App\Support\FilamentAuthRedirect;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Trait that redirects authenticated users to a role-based URL using
 * FilamentAuthRedirect after login, registration, or email verification.
 */
trait RedirectsAfterAuth
{
    /**
     * Creates a redirect response to the role-based URL for the
     * authenticated user.
     *
     * @param  mixed  $request  The incoming request.
     */
    public function toResponse(mixed $request): RedirectResponse|Redirector
    {
        return redirect()->to(FilamentAuthRedirect::for($request->user(Filament::getAuthGuard())));
    }
}
