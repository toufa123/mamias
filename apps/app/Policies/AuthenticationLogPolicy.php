<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

/**
 * The authentication log holds every user's IP addresses and devices, so only
 * super_admin reads it. The log is written by the package, never by hand, so
 * the missing write abilities deny by default.
 */
class AuthenticationLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, AuthenticationLog $authenticationLog): bool
    {
        return $user->hasRole('super_admin');
    }
}
