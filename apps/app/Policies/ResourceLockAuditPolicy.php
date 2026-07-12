<?php

declare(strict_types=1);

namespace App\Policies;

use Blendbyte\FilamentResourceLock\Models\ResourceLockAudit;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy governing access to ResourceLockAudit records.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class ResourceLockAuditPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any resource lock audit record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ResourceLockAudit');
    }

    /**
     * Authorize viewing a specific resource lock audit record.
     */
    public function view(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('View:ResourceLockAudit');
    }

    /**
     * Authorize creating a resource lock audit record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ResourceLockAudit');
    }

    /**
     * Authorize updating a specific resource lock audit record.
     */
    public function update(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('Update:ResourceLockAudit');
    }

    /**
     * Authorize deleting a specific resource lock audit record.
     */
    public function delete(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('Delete:ResourceLockAudit');
    }

    /**
     * Authorize bulk deletion of resource lock audit records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ResourceLockAudit');
    }

    /**
     * Authorize restoring a soft-deleted resource lock audit record.
     */
    public function restore(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('Restore:ResourceLockAudit');
    }

    /**
     * Authorize force-deleting a specific resource lock audit record.
     */
    public function forceDelete(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('ForceDelete:ResourceLockAudit');
    }

    /**
     * Authorize bulk force-deletion of resource lock audit records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ResourceLockAudit');
    }

    /**
     * Authorize bulk restoration of resource lock audit records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ResourceLockAudit');
    }

    /**
     * Authorize replicating a specific resource lock audit record.
     */
    public function replicate(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('Replicate:ResourceLockAudit');
    }

    /**
     * Authorize reordering resource lock audit records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ResourceLockAudit');
    }
}
