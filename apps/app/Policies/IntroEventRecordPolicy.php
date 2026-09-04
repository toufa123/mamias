<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IntroEventRecord;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class IntroEventRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IntroEventRecord');
    }

    public function view(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('View:IntroEventRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IntroEventRecord');
    }

    public function update(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('Update:IntroEventRecord');
    }

    public function delete(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('Delete:IntroEventRecord');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:IntroEventRecord');
    }

    public function restore(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('Restore:IntroEventRecord');
    }

    public function forceDelete(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('ForceDelete:IntroEventRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IntroEventRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IntroEventRecord');
    }

    public function replicate(AuthUser $authUser, IntroEventRecord $introEventRecord): bool
    {
        return $authUser->can('Replicate:IntroEventRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IntroEventRecord');
    }
}
