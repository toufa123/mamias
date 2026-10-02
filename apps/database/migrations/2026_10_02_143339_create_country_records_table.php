<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Create the country_records table: every country a species is recorded in, not only the first. */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('country_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intro_event_id')->constrained('intro_event_records')->cascadeOnDelete();
            $table->string('country'); // Stored as a name, like intro_event_records.first_country
            $table->string('establishment_status')->nullable(); // From EstablishmentStatus Enum
            $table->integer('first_record_year')->nullable();
            $table->foreignId('literature_id')->nullable()->constrained('literatures')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['intro_event_id', 'country']);
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_records');
    }
};
