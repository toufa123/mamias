<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staging area for the PAN Mediterranean import.
 *
 * The supplementary workbook does not carry every value MAMIAS stores: there
 * is no Med-wide establishment status anywhere in it, and the PAN sheet's four
 * subregion columns hold a year only. Those values have to be *proposed* from
 * what the file does say, and a proposal is not a fact — it needs a human.
 *
 * So the import lands here instead of in intro_event_records. An admin reviews
 * a row, confirms or overrides each proposed field, and only then is it
 * promoted to the live tables. Nothing reaches the catalogue unreviewed, and
 * an import that turns out wrong is dropped by deleting staging rows rather
 * than by unpicking live records.
 *
 * The four subregions are columns rather than a child table on purpose: the
 * set is closed (Subregion enum has exactly four cases) and one row per
 * species keeps the review screen a flat, sortable list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staging_intro_events', function (Blueprint $table): void {
            $table->id();

            // Provenance. Which run produced the row, and where it sat in the
            // file — without the row number a reviewer cannot go back to the
            // spreadsheet and check what the importer was looking at.
            $table->foreignId('import_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('source_row')->nullable();
            $table->string('source_sheet')->nullable();

            // The species as written in the file, kept verbatim even when it
            // resolves cleanly: it is the only way to audit a bad match later.
            $table->string('raw_species')->nullable();
            $table->string('raw_author')->nullable();
            // 'taxas', not the 'taxa' Laravel infers from taxon_id.
            $table->foreignId('taxon_id')->nullable()->constrained('taxas')->nullOnDelete();

            // Med-wide values under review.
            $table->string('nis_status')->nullable();
            $table->string('establishment_status')->nullable();
            $table->integer('first_introduction_year')->nullable();
            $table->string('first_country')->nullable();

            // Per-subregion values under review. Status is usually a proposal
            // here — the PAN sheet supplies only the year.
            foreach (['wmed', 'cmed', 'adria', 'emed'] as $subregion) {
                $table->string("{$subregion}_establishment_status")->nullable();
                $table->integer("{$subregion}_first_arrival_year")->nullable();
            }

            // Per-field provenance: raw cell value, what was proposed, and why.
            // Shaped {field: {raw, proposed, reason}} so the review screen can
            // explain any single field without re-deriving anything.
            $table->json('proposals')->nullable();

            $table->text('notes')->nullable();

            // pending → confirmed → promoted, or rejected at any point.
            $table->string('review_status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // Set when the row reaches the live tables. Its presence is what
            // makes promotion idempotent — a second promote is a no-op rather
            // than a duplicate intro event.
            $table->foreignId('promoted_intro_event_id')->nullable()
                ->constrained('intro_event_records')->nullOnDelete();
            $table->timestamp('promoted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // The review screen is filtered by status and sorted by file order.
            $table->index(['review_status', 'source_row']);
            $table->index('taxon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staging_intro_events');
    }
};
