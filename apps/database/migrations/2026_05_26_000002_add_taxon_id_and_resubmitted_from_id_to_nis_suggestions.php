<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->foreignId('taxon_id')->nullable()->after('rejection_reason')->constrained('taxas')->nullOnDelete();
            $table->foreignId('resubmitted_from_id')->nullable()->after('taxon_id')->constrained('nis_suggestions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('taxon_id');
            $table->dropConstrainedForeignId('resubmitted_from_id');
        });
    }
};
