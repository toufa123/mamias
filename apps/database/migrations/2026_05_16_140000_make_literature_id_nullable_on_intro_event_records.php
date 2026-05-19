<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->foreignId('literature_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table) {
            $table->foreignId('literature_id')->nullable(false)->change();
        });
    }
};
