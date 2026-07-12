<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;

/**
 * Policy governing access to Spatie Role entities.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any role.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Role');
    }

    /**
     * Authorize viewing a specific role.
     */
    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('View:Role');
    }

    /**
     * Authorize creating a role.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Role');
    }

    /**
     * Authorize updating a specific role.
     */
    public function update(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Update:Role');
    }

    /**
     * Authorize deleting a specific role.
     */
    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Delete:Role');
    }

    /**
     * Authorize bulk deletion of roles.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Role');
    }

    /**
     * Authorize restoring a soft-deleted role.
     */
    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Restore:Role');
    }

    /**
     * Authorize force-deleting a specific role.
     */
    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('ForceDelete:Role');
    }

    /**
     * Authorize bulk force-deletion of roles.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Role');
    }

    /**
     * Authorize bulk restoration of roles.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Role');
    }

    /**
     * Authorize replicating a specific role.
     */
    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Replicate:Role');
    }

    /**
     * Authorize reordering roles.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Role');
    }
}
