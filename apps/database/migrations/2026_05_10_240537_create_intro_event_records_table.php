<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intro_event_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxon_id')->constrained('taxas')->onDelete('cascade');
            $table->integer('first_introduction_year')->nullable();
            $table->string('first_country', 255)->nullable();
            $table->string('nis_status')->nullable();
            $table->string('establishment_status')->nullable();
            $table->foreignId('literature_id')->constrained()->onDelete('cascade');
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
        Schema::dropIfExists('intro_event_records');
    }
};
