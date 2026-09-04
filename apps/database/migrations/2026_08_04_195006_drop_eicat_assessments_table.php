<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('eicat_assessments');
    }

    public function down(): void
    {
        Schema::create('eicat_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intro_event_record_id')->constrained('intro_event_records')->cascadeOnDelete();
            $table->string('category');
            $table->string('mechanism');
            $table->string('confidence')->default('medium');
            $table->text('rationale')->nullable();
            $table->string('assessment_scale')->nullable();
            $table->date('assessed_at')->nullable();
            $table->foreignId('literature_id')->nullable()->constrained('literatures')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }
};
