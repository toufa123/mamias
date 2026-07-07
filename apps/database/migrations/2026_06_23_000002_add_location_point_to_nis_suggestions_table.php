<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE nis_suggestions ADD COLUMN IF NOT EXISTS location_point geometry(Point, 4326)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_nis_suggestions_location_point ON nis_suggestions USING GIST (location_point)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_nis_suggestions_location_point');
        DB::statement('ALTER TABLE nis_suggestions DROP COLUMN IF EXISTS location_point');
    }
};
