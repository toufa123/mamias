<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Drop abundance_count and add kingdom column to nis_suggestions table. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->dropColumn('abundance_count');
        });

        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->string('kingdom')->nullable()->after('depth');
        });
    }

    public function down(): void
    {
        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->dropColumn('kingdom');
        });

        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->integer('abundance_count')->nullable()->after('depth');
        });
    }
};
