<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The species name the record was published under, kept when the taxon
     * is later moved to its accepted name (Darwin Core verbatimIdentification).
     */
    public function up(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->string('verbatim_name')->nullable()->after('taxon_id');
        });
    }

    public function down(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->dropColumn('verbatim_name');
        });
    }
};
