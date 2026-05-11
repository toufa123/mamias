<?php

namespace App\Observers;

use App\Models\Literature;
use Illuminate\Support\Facades\Log;

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
        Log::info("New literature created with code: {$literature->code}");
    }
}
