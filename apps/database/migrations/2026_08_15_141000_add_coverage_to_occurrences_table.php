<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Add the coverage figure, its unit and how it was obtained to occurrences. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->decimal('coverage_value', 12, 2)->nullable()->after('acfor_scale');
            $table->string('coverage_unit')->nullable()->after('coverage_value');
            $table->string('coverage_method')->nullable()->after('coverage_unit');
        });
    }

    public function down(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->dropColumn(['coverage_value', 'coverage_unit', 'coverage_method']);
        });
    }
};
