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
        Schema::create('literatures', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('short_ref')->nullable();
            $table->text('full_ref')->nullable();
            $table->text('link')->nullable();
            $table->string('doi')->unique()->nullable();
            $table->string('type')->nullable();
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->userstamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('literatures');
    }
};
