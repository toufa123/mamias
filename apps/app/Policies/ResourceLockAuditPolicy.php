<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Blendbyte\FilamentResourceLock\Models\ResourceLockAudit;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourceLockAuditPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ResourceLockAudit');
    }

    public function view(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('View:ResourceLockAudit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ResourceLockAudit');
    }

    public function update(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('Update:ResourceLockAudit');
    }

    public function delete(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('Delete:ResourceLockAudit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ResourceLockAudit');
    }

    public function restore(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('Restore:ResourceLockAudit');
    }

    public function forceDelete(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('ForceDelete:ResourceLockAudit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ResourceLockAudit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ResourceLockAudit');
    }

    public function replicate(AuthUser $authUser, ResourceLockAudit $resourceLockAudit): bool
    {
        return $authUser->can('Replicate:ResourceLockAudit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ResourceLockAudit');
    }

}