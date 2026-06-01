<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->integer('abundance_count')->nullable()->after('depth');
            $table->string('abundance_category', 20)->nullable()->after('abundance_count');
        });
    }

    public function down(): void
    {
        Schema::table('nis_suggestions', function (Blueprint $table) {
            $table->dropColumn(['abundance_count', 'abundance_category']);
        });
    }
};
