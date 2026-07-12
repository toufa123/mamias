<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Create the taxas table with WoRMS taxonomy hierarchy and synonyms. */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('taxas', function (Blueprint $table) {
            $table->id();

            // WorMS Identifier
            $table->integer('aphia_id')->nullable();

            // Basic Information
            $table->string('url')->nullable();
            $table->string('scientificname')->nullable()->unique()->index();
            $table->string('authority')->nullable();
            $table->string('worms_status')->nullable();
            $table->string('catalogue_status')->nullable();
            $table->text('unacceptreason')->nullable();
            $table->string('rank')->nullable();

            // Taxonomy Hierarchy
            $table->string('kingdom')->nullable();
            $table->string('phylum')->nullable();
            $table->string('class')->nullable();
            $table->string('order')->nullable();
            $table->string('family')->nullable();
            $table->string('genus')->nullable();

            // Identifiers and Metadata
            $table->string('lsid')->nullable();
            $table->text('Easin_id')->nullable();
            $table->boolean('is_extinct')->nullable();
            $table->string('environments')->nullable();
            $table->json('synonyms_data')->nullable();

            $table->dateTime('fetched_at')->nullable();
            $table->text('notes')->nullable();
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxas');
    }
};
