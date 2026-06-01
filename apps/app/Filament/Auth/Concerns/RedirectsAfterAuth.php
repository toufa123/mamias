<?php

declare(strict_types=1);

namespace App\Filament\Auth\Concerns;

use App\Support\FilamentAuthRedirect;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

trait RedirectsAfterAuth
{
    public function toResponse(mixed $request): RedirectResponse|Redirector
    {
        return redirect()->to(FilamentAuthRedirect::for($request->user(Filament::getAuthGuard())));
    }
}
