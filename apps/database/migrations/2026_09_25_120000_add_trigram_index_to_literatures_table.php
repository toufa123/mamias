<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trigram matching on the full reference, so the form can warn about
 * near-duplicates (same paper, different punctuation or casing).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS literatures_full_ref_trgm_index ON literatures USING gin (full_ref gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS literatures_full_ref_trgm_index');
    }
};
