<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Add the alert-box.alerts setting to the settings store. */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('alert-box.alerts', []);
    }
};
