<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Create the eicat_assessments table for EICAT category and mechanism assessments. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eicat_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intro_event_record_id')->constrained('intro_event_records')->onDelete('cascade');
            $table->string('category');
            $table->string('mechanism');
            $table->string('confidence')->default('medium');
            $table->text('rationale')->nullable();
            $table->string('assessment_scale')->nullable();
            $table->date('assessed_at')->nullable();
            $table->foreignId('literature_id')->nullable()->constrained('literatures')->onDelete('set null');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eicat_assessments');
    }
};
