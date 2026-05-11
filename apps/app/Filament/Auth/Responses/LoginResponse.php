<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Support\FilamentAuthRedirect;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse(mixed $request): RedirectResponse | Redirector
    {
        return redirect()->to(FilamentAuthRedirect::for($request->user(Filament::getAuthGuard())));
    }
}

