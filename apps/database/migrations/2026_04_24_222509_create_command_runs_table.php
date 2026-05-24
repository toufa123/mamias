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
        Schema::create(config('command-runner.table_name'), function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('process_id')->nullable();
            $table->string('command', 500);
            $table->unsignedBigInteger('ran_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('killed_at')->nullable();
            $table->unsignedInteger('exit_code')->nullable();
            $table->longText('output')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_runs');
    }
};
