<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Activitylog\Models\Activity;

/**
 * Policy governing access to Activity log records.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class ActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any activity record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Activity');
    }

    /**
     * Authorize viewing a specific activity record.
     */
    public function view(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('View:Activity');
    }

    /**
     * Authorize creating an activity record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Activity');
    }

    /**
     * Authorize updating a specific activity record.
     */
    public function update(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('Update:Activity');
    }

    /**
     * Authorize deleting a specific activity record.
     */
    public function delete(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('Delete:Activity');
    }

    /**
     * Authorize bulk deletion of activity records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Activity');
    }

    /**
     * Authorize restoring a soft-deleted activity record.
     */
    public function restore(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('Restore:Activity');
    }

    /**
     * Authorize force-deleting a specific activity record.
     */
    public function forceDelete(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('ForceDelete:Activity');
    }

    /**
     * Authorize bulk force-deletion of activity records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Activity');
    }

    /**
     * Authorize bulk restoration of activity records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Activity');
    }

    /**
     * Authorize replicating a specific activity record.
     */
    public function replicate(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('Replicate:Activity');
    }

    /**
     * Authorize reordering activity records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Activity');
    }
}
