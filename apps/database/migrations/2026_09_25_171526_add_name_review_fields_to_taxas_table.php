<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Review of WoRMS name changes: how sure the proposed accepted name is the
     * same species, names the team chose not to follow, and who reviews.
     */
    public function up(): void
    {
        Schema::table('taxas', function (Blueprint $table) {
            $table->unsignedBigInteger('proposed_accepted_aphia_id')->nullable()->after('proposed_accepted_name');
            $table->unsignedTinyInteger('name_change_confidence')->nullable()->after('proposed_accepted_aphia_id');
            $table->json('name_change_reasons')->nullable()->after('name_change_confidence');
            $table->string('dismissed_accepted_name')->nullable()->after('name_change_reasons');
            $table->text('dismissed_reason')->nullable()->after('dismissed_accepted_name');
            $table->foreignId('name_reviewer_id')->nullable()->after('dismissed_reason')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('taxas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('name_reviewer_id');
            $table->dropColumn(['proposed_accepted_aphia_id', 'name_change_confidence', 'name_change_reasons', 'dismissed_accepted_name', 'dismissed_reason']);
        });
    }
};
