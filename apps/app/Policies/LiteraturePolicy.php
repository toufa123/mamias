<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Literature;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy governing access to Literature entities.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class LiteraturePolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any literature record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Literature');
    }

    /**
     * Authorize viewing a specific literature record.
     */
    public function view(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('View:Literature');
    }

    /**
     * Authorize creating a literature record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Literature');
    }

    /**
     * Authorize updating a specific literature record.
     */
    public function update(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('Update:Literature');
    }

    /**
     * Authorize deleting a specific literature record.
     */
    public function delete(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('Delete:Literature');
    }

    /**
     * Authorize bulk deletion of literature records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Literature');
    }

    /**
     * Authorize restoring a soft-deleted literature record.
     */
    public function restore(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('Restore:Literature');
    }

    /**
     * Authorize force-deleting a specific literature record.
     */
    public function forceDelete(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('ForceDelete:Literature');
    }

    /**
     * Authorize bulk force-deletion of literature records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Literature');
    }

    /**
     * Authorize bulk restoration of literature records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Literature');
    }

    /**
     * Authorize replicating a specific literature record.
     */
    public function replicate(AuthUser $authUser, Literature $literature): bool
    {
        return $authUser->can('Replicate:Literature');
    }

    /**
     * Authorize reordering literature records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Literature');
    }
}
