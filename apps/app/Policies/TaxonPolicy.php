<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Taxon;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy governing access to Taxon entities.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class TaxonPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any taxon record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Taxon');
    }

    /**
     * Authorize viewing a specific taxon record.
     */
    public function view(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('View:Taxon');
    }

    /**
     * Authorize creating a taxon record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Taxon');
    }

    /**
     * Authorize updating a specific taxon record.
     */
    public function update(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('Update:Taxon');
    }

    /**
     * Authorize deleting a specific taxon record.
     */
    public function delete(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('Delete:Taxon');
    }

    /**
     * Authorize bulk deletion of taxon records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Taxon');
    }

    /**
     * Authorize restoring a soft-deleted taxon record.
     */
    public function restore(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('Restore:Taxon');
    }

    /**
     * Authorize force-deleting a specific taxon record.
     */
    public function forceDelete(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('ForceDelete:Taxon');
    }

    /**
     * Authorize bulk force-deletion of taxon records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Taxon');
    }

    /**
     * Authorize bulk restoration of taxon records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Taxon');
    }

    /**
     * Authorize replicating a specific taxon record.
     */
    public function replicate(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('Replicate:Taxon');
    }

    /**
     * Authorize reordering taxon records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Taxon');
    }
}
