<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Make taxon_id not nullable on intro_event_records table. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->foreignId('taxon_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->foreignId('taxon_id')->nullable()->change();
        });
    }
};
