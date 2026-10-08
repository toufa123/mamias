<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Crossref metadata/retraction sync, DOI suggestions and WoRMS original descriptions.
Schedule::command('literature:enrich --search --descriptions')->weekly()->withoutOverlapping();

// Heartbeat job the Health "Queue" check waits for; fails if the worker stops picking jobs up.
Schedule::command('health:queue-check-heartbeat')->everyMinute();
// New WoRMS accepted names, scored and listed under NIS Taxon › Name to update.
Schedule::command('taxa:check-worms-names')->monthlyOn(1, '03:00')->withoutOverlapping();
