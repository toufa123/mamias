<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IntroEventRecord;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy governing access to IntroEventRecord entities.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class IntroEventRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any introduction event record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IntroEventRecord');
    }

    /**
     * Authorize viewing a specific introduction event record.
     */
    public function view(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('View:IntroEventRecord');
    }

    /**
     * Authorize creating an introduction event record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IntroEventRecord');
    }

    /**
     * Authorize updating a specific introduction event record.
     */
    public function update(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('Update:IntroEventRecord');
    }

    /**
     * Authorize deleting a specific introduction event record.
     */
    public function delete(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('Delete:IntroEventRecord');
    }

    /**
     * Authorize bulk deletion of introduction event records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:IntroEventRecord');
    }

    /**
     * Authorize restoring a soft-deleted introduction event record.
     */
    public function restore(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('Restore:IntroEventRecord');
    }

    /**
     * Authorize force-deleting a specific introduction event record.
     */
    public function forceDelete(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('ForceDelete:IntroEventRecord');
    }

    /**
     * Authorize bulk force-deletion of introduction event records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IntroEventRecord');
    }

    /**
     * Authorize bulk restoration of introduction event records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IntroEventRecord');
    }

    /**
     * Authorize replicating a specific introduction event record.
     */
    public function replicate(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('Replicate:IntroEventRecord');
    }

    /**
     * Authorize reordering introduction event records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IntroEventRecord');
    }
}
