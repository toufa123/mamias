<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A pathway is known at CBD category level more often than at subcategory
 * level, so the subcategory is optional; the category stays required.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pathway_records', function (Blueprint $table): void {
            $table->string('subcategory')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pathway_records', function (Blueprint $table): void {
            $table->string('subcategory')->nullable(false)->change();
        });
    }
};
