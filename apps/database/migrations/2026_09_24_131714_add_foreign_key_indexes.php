<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index the app's foreign keys. PostgreSQL does not index a foreign key column
 * on its own, so joins, filters and parent deletes were scanning the child table.
 * Package-owned tables (comments, imports, layup, permissions) are left alone.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->index('taxon_id');
            $table->index('literature_id');
        });

        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->index('taxon_id');
            $table->index('resubmitted_from_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
        });

        Schema::table('nis_suggestion_literature', function (Blueprint $table) {
            $table->index('literature_id');
        });

        Schema::table('occurrences', function (Blueprint $table) {
            $table->index('intro_event_record_id');
            $table->index('user_id');
        });

        Schema::table('pathway_records', function (Blueprint $table) {
            $table->index('intro_event_id');
        });

        Schema::table('subregion_records', function (Blueprint $table) {
            $table->index('intro_event_id');
        });

        Schema::table('staging_intro_events', function (Blueprint $table) {
            $table->index('import_id');
            $table->index('promoted_intro_event_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->dropIndex(['taxon_id']);
            $table->dropIndex(['literature_id']);
        });

        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->dropIndex(['taxon_id']);
            $table->dropIndex(['resubmitted_from_id']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['deleted_by']);
        });

        Schema::table('nis_suggestion_literature', function (Blueprint $table) {
            $table->dropIndex(['literature_id']);
        });

        Schema::table('occurrences', function (Blueprint $table) {
            $table->dropIndex(['intro_event_record_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('pathway_records', function (Blueprint $table) {
            $table->dropIndex(['intro_event_id']);
        });

        Schema::table('subregion_records', function (Blueprint $table) {
            $table->dropIndex(['intro_event_id']);
        });

        Schema::table('staging_intro_events', function (Blueprint $table) {
            $table->dropIndex(['import_id']);
            $table->dropIndex(['promoted_intro_event_id']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['reviewed_by']);
        });
    }
};
