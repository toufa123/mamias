<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Add per-subregion establishment_status to subregion_records (the baseline files store est/cas/unk per subregion). */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subregion_records', function (Blueprint $table) {
            $table->string('establishment_status')->nullable()->after('nis_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subregion_records', function (Blueprint $table) {
            $table->dropColumn('establishment_status');
        });
    }
};
