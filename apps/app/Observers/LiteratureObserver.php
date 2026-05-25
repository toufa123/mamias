<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Literature;
use App\Models\User;
use App\Notifications\NewLiteratureReferenceNotification;
use Illuminate\Support\Facades\Notification;

class LiteratureObserver
{
    public function creating(Literature $literature): void
    {
        if (empty($literature->code)) {
            $literature->code = Literature::generateNextCode();
        }
    }

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
