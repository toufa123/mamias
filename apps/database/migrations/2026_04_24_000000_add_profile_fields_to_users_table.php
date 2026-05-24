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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('title')->nullable()->after('last_name');
            $table->string('phone')->nullable()->after('title');
            $table->boolean('has_whatsapp')->default(false)->after('phone');
            $table->string('country')->nullable()->after('has_whatsapp');
            $table->json('taxonomic_area')->nullable()->after('country');
            $table->json('subregions')->nullable()->after('taxonomic_area');
            $table->json('countries')->nullable()->after('subregions');
            $table->text('bio')->nullable()->after('countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'title',
                'phone',
                'has_whatsapp',
                'country',
                'taxonomic_area',
                'subregions',
                'countries',
                'bio',
            ]);
        });
    }
};
