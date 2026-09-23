<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert taxas.environments from varchar to jsonb.
 *
 * The column has always held a JSON array — Taxon casts it to `array` and every
 * writer goes through that cast — but the declared varchar type forced each
 * containment query to cast the column to jsonb per row, which no index can
 * serve. The taxon table's environment filter (TaxonTable, orWhereJsonContains)
 * is the query that pays for that, hence the GIN index.
 *
 * jsonb also rejects malformed JSON on write, rather than storing it and
 * returning null from the cast on every later read.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A value that is not array-shaped cannot be cast and would abort the
        // ALTER. The `array` cast already reads those as null, so clearing them
        // loses nothing. Anything array-shaped but malformed is deliberately
        // left to fail the ALTER loudly instead of being discarded silently.
        DB::statement(<<<'SQL'
            UPDATE taxas
            SET environments = NULL
            WHERE environments IS NOT NULL
              AND environments !~ '^\s*\[.*\]\s*$'
        SQL);

        DB::statement('ALTER TABLE taxas ALTER COLUMN environments TYPE jsonb USING environments::jsonb');

        Schema::table('taxas', function (Blueprint $table): void {
            // Default jsonb_ops — it covers the `@>` containment operator that
            // whereJsonContains compiles to.
            $table->index('environments', 'taxas_environments_gin', 'gin');
        });
    }

    public function down(): void
    {
        Schema::table('taxas', function (Blueprint $table): void {
            $table->dropIndex('taxas_environments_gin');
        });

        DB::statement('ALTER TABLE taxas ALTER COLUMN environments TYPE varchar(255) USING environments::text');
    }
};
