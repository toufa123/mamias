<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Filament\Auth\Concerns\RedirectsAfterAuth;
use Filament\Auth\Http\Responses\Contracts\EmailVerificationResponse as EmailVerificationResponseContract;

/**
 * Response handler that redirects the user after successful email
 * verification based on their role.
 */
class EmailVerificationResponse implements EmailVerificationResponseContract
{
    use RedirectsAfterAuth;
}
