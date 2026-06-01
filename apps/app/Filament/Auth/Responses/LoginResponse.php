<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Filament\Auth\Concerns\RedirectsAfterAuth;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    use RedirectsAfterAuth;
}
