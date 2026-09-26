<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crossref enrichment for literatures, and the WoRMS original description on taxa.
 *
 * crossref_checked_at is the last time Crossref was asked about the record: a
 * metadata/retraction sync when it has a DOI, a DOI search when it has none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('literatures', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->boolean('is_retracted')->default(false);
            $table->string('suggested_doi')->nullable();
            $table->timestamp('crossref_checked_at')->nullable();
        });

        Schema::table('taxas', function (Blueprint $table) {
            $table->foreignId('original_description_id')->nullable()->index()
                ->constrained('literatures')->nullOnDelete();
        });

        // Normalize stored DOIs to the bare lowercase form the model now writes.
        // A row whose normalized form already exists is a duplicate and is left
        // as-is for a curator, rather than failing the unique constraint here.
        $normalized = "lower(regexp_replace(trim(doi), '^(https?://(dx\\.)?doi\\.org/|doi:\\s*)', '', 'i'))";

        DB::statement("
            UPDATE literatures l SET doi = {$normalized}
            WHERE doi IS NOT NULL
              AND doi <> {$normalized}
              AND NOT EXISTS (SELECT 1 FROM literatures o WHERE o.id <> l.id AND o.doi = {$normalized})
        ");
    }

    public function down(): void
    {
        Schema::table('taxas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('original_description_id');
        });

        Schema::table('literatures', function (Blueprint $table) {
            $table->dropColumn(['year', 'is_retracted', 'suggested_doi', 'crossref_checked_at']);
        });
    }
};
