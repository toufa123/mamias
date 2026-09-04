<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Add uncertainty (DataQuality) to pathway_records — the PathwayRecord model already casts it. */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pathway_records', function (Blueprint $table) {
            $table->string('uncertainty')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pathway_records', function (Blueprint $table) {
            $table->dropColumn('uncertainty');
        });
    }
};
