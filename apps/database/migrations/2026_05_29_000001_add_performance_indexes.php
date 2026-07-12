<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Add performance indexes to taxas, nis_suggestions, literatures, intro_event_records, subregion_records and pathway_records tables. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxas', function (Blueprint $table) {
            $table->index('kingdom');
            $table->index('phylum');
            $table->index('family');
            $table->index('rank');
            $table->index('catalogue_status');
            $table->index('aphia_id');
        });

        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
        });

        Schema::table('literatures', function (Blueprint $table) {
            $table->index('created_by');
            $table->index('status');
        });

        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->index('nis_status');
            $table->index('establishment_status');
            $table->index('first_country');
        });

        Schema::table('subregion_records', function (Blueprint $table) {
            $table->index('subregion');
            $table->index('nis_status');
        });

        Schema::table('pathway_records', function (Blueprint $table) {
            $table->index('category');
            $table->index('pathway_type');
        });
    }

    public function down(): void
    {
        Schema::table('taxas', function (Blueprint $table) {
            $table->dropIndex(['kingdom']);
            $table->dropIndex(['phylum']);
            $table->dropIndex(['family']);
            $table->dropIndex(['rank']);
            $table->dropIndex(['catalogue_status']);
            $table->dropIndex(['aphia_id']);
        });

        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('literatures', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['status']);
        });

        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->dropIndex(['nis_status']);
            $table->dropIndex(['establishment_status']);
            $table->dropIndex(['first_country']);
        });

        Schema::table('subregion_records', function (Blueprint $table) {
            $table->dropIndex(['subregion']);
            $table->dropIndex(['nis_status']);
        });

        Schema::table('pathway_records', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['pathway_type']);
        });
    }
};
