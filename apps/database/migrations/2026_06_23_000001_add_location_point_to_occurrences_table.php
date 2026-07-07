<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE occurrences ADD COLUMN IF NOT EXISTS location_point geometry(Point, 4326)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_occurrences_location_point ON occurrences USING GIST (location_point)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_occurrences_location_point');
        DB::statement('ALTER TABLE occurrences DROP COLUMN IF EXISTS location_point');
    }
};
