<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class LogRoleChangeListener
{
    public function handle(RoleAttachedEvent|RoleDetachedEvent|PermissionAttachedEvent|PermissionDetachedEvent $event): void
    {
        $roleNames = $this->resolveRoleNames($event->rolesOrIds);

        $activity = activity()
            ->performedOn($event->model)
            ->withProperties(['roles' => $roleNames]);

        if (auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        match ($event::class) {
            RoleAttachedEvent::class => $activity->log('role_assigned'),
            RoleDetachedEvent::class => $activity->log('role_removed'),
            PermissionAttachedEvent::class => $activity->log('permission_assigned'),
            PermissionDetachedEvent::class => $activity->log('permission_removed'),
        };
    }

    private function resolveRoleNames(mixed $rolesOrIds): array
    {
        if ($rolesOrIds instanceof Role) {
            return [$rolesOrIds->name];
        }

        if ($rolesOrIds instanceof Collection) {
            return $rolesOrIds->map(fn ($role) => $role instanceof Role ? $role->name : (string) $role)->values()->toArray();
        }

        if (is_array($rolesOrIds)) {
            return array_map(fn ($role) => $role instanceof Role ? $role->name : (string) $role, $rolesOrIds);
        }

        return [(string) $rolesOrIds];
    }
}
