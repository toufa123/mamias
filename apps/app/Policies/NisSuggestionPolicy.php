<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NisSuggestion;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class NisSuggestionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NisSuggestion');
    }

    public function view(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('View:NisSuggestion');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NisSuggestion');
    }

    public function update(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('Update:NisSuggestion');
    }

    public function delete(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('Delete:NisSuggestion');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NisSuggestion');
    }

    public function restore(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('Restore:NisSuggestion');
    }

    public function forceDelete(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('ForceDelete:NisSuggestion');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NisSuggestion');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NisSuggestion');
    }

    public function replicate(AuthUser $authUser, NisSuggestion $nisSuggestion): bool
    {
        return $authUser->can('Replicate:NisSuggestion');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NisSuggestion');
    }
}
