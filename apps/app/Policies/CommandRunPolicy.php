<?php

declare(strict_types=1);

namespace App\Policies;

use BinaryBuilds\CommandRunner\Models\CommandRun;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy governing access to CommandRun records.
 *
 * Delegates authorization to Spatie permission checks using the format "Action:Entity".
 */
class CommandRunPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize viewing any command run record.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CommandRun');
    }

    /**
     * Authorize viewing a specific command run record.
     */
    public function view(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('View:CommandRun');
    }

    /**
     * Authorize creating a command run record.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CommandRun');
    }

    /**
     * Authorize updating a specific command run record.
     */
    public function update(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('Update:CommandRun');
    }

    /**
     * Authorize deleting a specific command run record.
     */
    public function delete(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('Delete:CommandRun');
    }

    /**
     * Authorize bulk deletion of command run records.
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CommandRun');
    }

    /**
     * Authorize restoring a soft-deleted command run record.
     */
    public function restore(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('Restore:CommandRun');
    }

    /**
     * Authorize force-deleting a specific command run record.
     */
    public function forceDelete(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('ForceDelete:CommandRun');
    }

    /**
     * Authorize bulk force-deletion of command run records.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CommandRun');
    }

    /**
     * Authorize bulk restoration of command run records.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CommandRun');
    }

    /**
     * Authorize replicating a specific command run record.
     */
    public function replicate(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('Replicate:CommandRun');
    }

    /**
     * Authorize reordering command run records.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CommandRun');
    }
}
