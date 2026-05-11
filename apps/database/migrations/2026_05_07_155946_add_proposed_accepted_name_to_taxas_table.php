<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('taxas', function (Blueprint $table) {
            $table->string('proposed_accepted_name')->nullable()->after('unacceptreason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxas', function (Blueprint $table) {
            $table->dropColumn('proposed_accepted_name');
        });
    }
};