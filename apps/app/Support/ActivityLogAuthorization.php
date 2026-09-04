<?php

namespace App\Support;

class ActivityLogAuthorization
{
    public function __invoke($user): bool
    {
        return $user->hasRole('super_admin');
    }
}
