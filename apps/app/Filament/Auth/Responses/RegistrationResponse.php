<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Support\FilamentAuthRedirect;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class RegistrationResponse implements RegistrationResponseContract
{
    public function toResponse(mixed $request): RedirectResponse|Redirector
    {
        return redirect()->to(FilamentAuthRedirect::for($request->user(Filament::getAuthGuard())));
    }
}
