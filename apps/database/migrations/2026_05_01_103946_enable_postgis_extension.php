<?php

use Clickbar\Magellan\Schema\MagellanSchema;
use Illuminate\Database\Migrations\Migration;

/** Enable the PostGIS extension for geographic data support. */
return new class extends Migration
{
    public function up(): void
    {
        MagellanSchema::enablePostgisIfNotExists($this->connection);
    }

    public function down(): void
    {
        MagellanSchema::disablePostgisIfExists($this->connection);
    }
};
