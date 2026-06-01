<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->dropColumn('abundance_category');
        });

        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->string('acfor_scale')->nullable()->after('abundance_count');
            $table->json('habitats')->nullable()->after('acfor_scale');
        });
    }

    public function down(): void
    {
        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->dropColumn(['acfor_scale', 'habitats']);
        });

        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->string('abundance_category')->nullable()->after('abundance_count');
        });
    }
};
