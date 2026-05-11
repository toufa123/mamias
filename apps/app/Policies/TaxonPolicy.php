<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Taxon;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Taxon');
    }

    public function view(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('View:Taxon');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Taxon');
    }

    public function update(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('Update:Taxon');
    }

    public function delete(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('Delete:Taxon');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Taxon');
    }

    public function restore(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('Restore:Taxon');
    }

    public function forceDelete(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('ForceDelete:Taxon');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Taxon');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Taxon');
    }

    public function replicate(AuthUser $authUser, Taxon $taxon): bool
    {
        return $authUser->can('Replicate:Taxon');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Taxon');
    }

}