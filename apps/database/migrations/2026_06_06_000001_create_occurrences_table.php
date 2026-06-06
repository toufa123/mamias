<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('intro_event_record_id')->constrained('intro_event_records');
            $table->json('location');
            $table->decimal('depth', 8, 2)->nullable();
            $table->json('habitats')->nullable();
            $table->json('photo_paths')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('observed_at');
            $table->string('status')->default('pending');
            $table->text('moderation_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occurrences');
    }
};
