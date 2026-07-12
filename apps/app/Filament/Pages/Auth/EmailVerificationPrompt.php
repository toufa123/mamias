<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;

/**
 * Email verification prompt page using the custom auth UI enhancer layout
 * and no top bar.
 */
class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    use HasCustomLayout;

    protected bool $hasTopbar = false;
}
