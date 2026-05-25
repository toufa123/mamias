<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Literature;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LiteraturePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Literature');
    }

    public function view(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('View:Literature');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Literature');
    }

    public function update(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('Update:Literature');
    }

    public function delete(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('Delete:Literature');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Literature');
    }

    public function restore(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('Restore:Literature');
    }

    public function forceDelete(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('ForceDelete:Literature');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Literature');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Literature');
    }

    public function replicate(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('Replicate:Literature');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Literature');
    }
}
