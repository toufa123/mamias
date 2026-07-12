<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Backfill location_point geometry from JSON location data on occurrences and nis_suggestions tables. */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE occurrences
            SET location_point = ST_SetSRID(ST_MakePoint(
                (location->0->>'lng')::float,
                (location->0->>'lat')::float
            ), 4326)
            WHERE location IS NOT NULL
              AND json_array_length(location) > 0
              AND location->0 IS NOT NULL
        ");

        DB::statement("
            UPDATE nis_suggestions
            SET location_point = ST_SetSRID(ST_MakePoint(
                (location->0->>'lng')::float,
                (location->0->>'lat')::float
            ), 4326)
            WHERE location IS NOT NULL
              AND json_array_length(location) > 0
              AND location->0 IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('UPDATE occurrences SET location_point = NULL');
        DB::statement('UPDATE nis_suggestions SET location_point = NULL');
    }
};
