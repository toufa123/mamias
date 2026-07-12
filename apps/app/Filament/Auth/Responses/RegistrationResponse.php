<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Filament\Auth\Concerns\RedirectsAfterAuth;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;

/**
 * Response handler that redirects the user after successful registration
 * based on their role.
 */
class RegistrationResponse implements RegistrationResponseContract
{
    use RedirectsAfterAuth;
}
