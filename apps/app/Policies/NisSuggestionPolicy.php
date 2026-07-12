<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NisSuggestion;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy governing access to NisSuggestion entities.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class NisSuggestionPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any NIS suggestion record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NisSuggestion');
    }

    /**
     * Authorize viewing a specific NIS suggestion record.
     */
    public function view(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('View:NisSuggestion');
    }

    /**
     * Authorize creating an NIS suggestion record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NisSuggestion');
    }

    /**
     * Authorize updating a specific NIS suggestion record.
     */
    public function update(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('Update:NisSuggestion');
    }

    /**
     * Authorize deleting a specific NIS suggestion record.
     */
    public function delete(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('Delete:NisSuggestion');
    }

    /**
     * Authorize bulk deletion of NIS suggestion records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NisSuggestion');
    }

    /**
     * Authorize restoring a soft-deleted NIS suggestion record.
     */
    public function restore(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('Restore:NisSuggestion');
    }

    /**
     * Authorize force-deleting a specific NIS suggestion record.
     */
    public function forceDelete(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('ForceDelete:NisSuggestion');
    }

    /**
     * Authorize bulk force-deletion of NIS suggestion records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NisSuggestion');
    }

    /**
     * Authorize bulk restoration of NIS suggestion records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NisSuggestion');
    }

    /**
     * Authorize replicating a specific NIS suggestion record.
     */
    public function replicate(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('Replicate:NisSuggestion');
    }

    /**
     * Authorize reordering NIS suggestion records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NisSuggestion');
    }
}
