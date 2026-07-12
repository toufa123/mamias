<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Literature;
use App\Models\User;
use App\Notifications\NewLiteratureReferenceNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Observes the Literature model for lifecycle events.
 *
 * Auto-generates a sequential code and notifies admins/scientists
 * when a new literature reference is created.
 */
class LiteratureObserver
{
    /**
     * Auto-assign a sequential code before the model is persisted.
     */
    public function creating(Literature $literature): void
    {
        if (empty($literature->code)) {
            $literature->code = Literature::generateNextCode();
        }
    }

    /**
     * Send a notification to all super admins and scientists after creation.
     */
    public function created(Literature $literature): void
    {
        $adminsAndScientists = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'scientist']);
        })->get();

        if ($adminsAndScientists->isNotEmpty()) {
            Notification::send($adminsAndScientists, new NewLiteratureReferenceNotification($literature));
        }
    }
}
