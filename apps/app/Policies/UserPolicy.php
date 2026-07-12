<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy governing access to User entities.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 * Note: view(), update(), delete(), forceDelete(), and replicate do not take a
 * model instance since the authenticated user is the subject.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any user record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    /**
     * Authorize viewing a user record.
     */
    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:User');
    }

    /**
     * Authorize creating a user record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    /**
     * Authorize updating a user record.
     */
    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:User');
    }

    /**
     * Authorize deleting a user record.
     */
    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:User');
    }

    /**
     * Authorize bulk deletion of user records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:User');
    }

    /**
     * Authorize restoring a soft-deleted user record.
     */
    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    /**
     * Authorize force-deleting a user record.
     */
    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    /**
     * Authorize bulk force-deletion of user records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    /**
     * Authorize bulk restoration of user records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    /**
     * Authorize replicating a user record.
     */
    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:User');
    }

    /**
     * Authorize reordering user records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }
}
