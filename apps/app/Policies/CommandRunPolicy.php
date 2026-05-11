<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use BinaryBuilds\CommandRunner\Models\CommandRun;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommandRunPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CommandRun');
    }

    public function view(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('View:CommandRun');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CommandRun');
    }

    public function update(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('Update:CommandRun');
    }

    public function delete(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('Delete:CommandRun');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CommandRun');
    }

    public function restore(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('Restore:CommandRun');
    }

    public function forceDelete(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('ForceDelete:CommandRun');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CommandRun');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CommandRun');
    }

    public function replicate(AuthUser $authUser, CommandRun $commandRun): bool
    {
        return $authUser->can('Replicate:CommandRun');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CommandRun');
    }

}