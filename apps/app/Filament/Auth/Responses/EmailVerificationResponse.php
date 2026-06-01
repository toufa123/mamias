<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Filament\Auth\Concerns\RedirectsAfterAuth;
use Filament\Auth\Http\Responses\Contracts\EmailVerificationResponse as EmailVerificationResponseContract;

class EmailVerificationResponse implements EmailVerificationResponseContract
{
    use RedirectsAfterAuth;
}
