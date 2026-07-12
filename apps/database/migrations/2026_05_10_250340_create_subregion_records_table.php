<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Create the subregion_records table for subregion-level NIS status. */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subregion_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intro_event_id')->constrained('intro_event_records')->onDelete('cascade');
            $table->string('subregion'); // From Subregion Enum
            $table->string('nis_status')->nullable(); // Establishment success
            $table->integer('first_arrival_year')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subregion_records');
    }
};
