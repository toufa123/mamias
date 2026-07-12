<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Create the pivot table nis_suggestion_literature for many-to-many relation. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nis_suggestion_literature', function (Blueprint $table) {
            $table->foreignId('nis_suggestion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('literature_id')->constrained()->cascadeOnDelete();
            $table->primary(['nis_suggestion_id', 'literature_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nis_suggestion_literature');
    }
};
