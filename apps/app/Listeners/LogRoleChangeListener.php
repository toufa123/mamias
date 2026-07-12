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
        $isPermissionEvent = $event instanceof PermissionAttachedEvent || $event instanceof PermissionDetachedEvent;

        $names = $isPermissionEvent
            ? $this->resolveNames($event->permissionsOrIds)
            : $this->resolveNames($event->rolesOrIds);

        $activity = activity()
            ->performedOn($event->model)
            ->withProperties([$isPermissionEvent ? 'permissions' : 'roles' => $names]);

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

    private function resolveNames(mixed $ids): array
    {
        if ($ids instanceof Role) {
            return [$ids->name];
        }

        if ($ids instanceof Collection) {
            return $ids->map(fn ($item) => $item instanceof Role ? $item->name : (string) $item)->values()->toArray();
        }

        if (is_array($ids)) {
            return array_map(fn ($item) => $item instanceof Role ? $item->name : (string) $item, $ids);
        }

        return [(string) $ids];
    }
}
