<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Create the pathway_records table for CBD pathway categories and subcategories. */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pathway_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intro_event_id')->constrained('intro_event_records')->onDelete('cascade');
            $table->string('category');
            $table->string('subcategory');
            $table->string('pathway_type'); // primary, secondary
            $table->text('description')->nullable();
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
        Schema::dropIfExists('pathway_records');
    }
};
