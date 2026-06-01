<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Filament\Auth\Concerns\RedirectsAfterAuth;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;

class RegistrationResponse implements RegistrationResponseContract
{
    use RedirectsAfterAuth;
}
