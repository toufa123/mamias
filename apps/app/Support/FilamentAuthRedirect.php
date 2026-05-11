<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class FilamentAuthRedirect
{
    public static function for(?Authenticatable $user): string
    {
        if (! $user) {
            return url('/');
        }

        if (($user instanceof MustVerifyEmail) && (! $user->hasVerifiedEmail())) {
            return route('filament.mamias.auth.email-verification.prompt');
        }

        if (($user instanceof User) && $user->hasAnyRole(['super_admin', 'scientist'])) {
            return filament()->getPanel('mamias')->getUrl();
        }

        return url('/');
    }
}
