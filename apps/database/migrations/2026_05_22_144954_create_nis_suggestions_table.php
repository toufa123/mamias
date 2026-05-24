<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nis_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('aphia_id')->nullable();
            $table->string('suggested_scientific_name');
            $table->string('authority')->nullable();
            $table->string('worms_status', 100)->nullable();
            $table->string('suggested_common_name')->nullable();
            $table->json('location')->nullable();
            $table->decimal('depth', 8, 2)->nullable();
            $table->string('bibliography', 1000)->nullable();
            $table->string('doi')->nullable();
            $table->json('photo_paths')->nullable();
            $table->json('document_paths')->nullable();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nis_suggestions');
    }
};
