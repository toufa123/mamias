<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->foreignId('taxon_id')->nullable()->change();
            $table->boolean('needs_review')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->dropColumn('needs_review');
            $table->foreignId('taxon_id')->nullable(false)->change();
        });
    }
};
