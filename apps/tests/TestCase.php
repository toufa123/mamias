<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Runs after the application boots but before trait setup (e.g. RefreshDatabase's
     * migrate:fresh). The dev container exports a real DB_DATABASE env var that can
     * override the intended test database; if the active connection is not a *_test
     * database, abort loudly instead of dropping every table in the developer's database.
     */
    protected function setUpTraits()
    {
        $database = DB::connection()->getDatabaseName();

        if (! str_ends_with((string) $database, '_test')) {
            throw new RuntimeException(
                "Refusing to run tests against non-test database [{$database}]. ".
                'Tests must use a database whose name ends in "_test". '.
                'Check phpunit.xml and any DB_DATABASE env var exported by the container.'
            );
        }

        return parent::setUpTraits();
    }
}
