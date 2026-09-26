<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who decided on a submitted reference, when, and what they told the submitter.
 * Kept apart from updated_by/updated_at, which later edits and the Crossref
 * sync also touch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('literatures', function (Blueprint $table) {
            $table->text('review_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('literatures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_comment', 'reviewed_at']);
        });
    }
};
