<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Filament\Auth\Concerns\RedirectsAfterAuth;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;

/**
 * Response handler that redirects the user after successful login based
 * on their role.
 */
class LoginResponse implements LoginResponseContract
{
    use RedirectsAfterAuth;
}
