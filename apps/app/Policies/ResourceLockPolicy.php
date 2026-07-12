<?php

declare(strict_types=1);

namespace App\Policies;

use Blendbyte\FilamentResourceLock\Models\ResourceLock;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy governing access to ResourceLock records.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class ResourceLockPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any resource lock record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ResourceLock');
    }

    /**
     * Authorize viewing a specific resource lock record.
     */
    public function view(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('View:ResourceLock');
    }

    /**
     * Authorize creating a resource lock record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ResourceLock');
    }

    /**
     * Authorize updating a specific resource lock record.
     */
    public function update(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('Update:ResourceLock');
    }

    /**
     * Authorize deleting a specific resource lock record.
     */
    public function delete(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('Delete:ResourceLock');
    }

    /**
     * Authorize bulk deletion of resource lock records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ResourceLock');
    }

    /**
     * Authorize restoring a soft-deleted resource lock record.
     */
    public function restore(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('Restore:ResourceLock');
    }

    /**
     * Authorize force-deleting a specific resource lock record.
     */
    public function forceDelete(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('ForceDelete:ResourceLock');
    }

    /**
     * Authorize bulk force-deletion of resource lock records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ResourceLock');
    }

    /**
     * Authorize bulk restoration of resource lock records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ResourceLock');
    }

    /**
     * Authorize replicating a specific resource lock record.
     */
    public function replicate(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('Replicate:ResourceLock');
    }

    /**
     * Authorize reordering resource lock records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ResourceLock');
    }
}
