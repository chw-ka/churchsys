<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against anything except an in-memory / file SQLite database.
     *
     * The test suite uses RefreshDatabase, which calls `migrate:fresh` and can
     * therefore drop every table in the database it is pointed at. The production
     * containers export DB_CONNECTION=mysql and DB_DATABASE=churchsys, so a stray
     * `php artisan test` inside the app container would otherwise aim at live
     * church data.
     *
     * phpunit.xml pins the connection to sqlite / :memory: with force="true";
     * this guard is the second line of defence, so a misconfiguration fails the
     * run immediately and loudly instead of touching real data.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        $isSqlite = $connection === 'sqlite'
            && (is_string($database) && (str_contains($database, ':memory:') || str_ends_with($database, '.sqlite')));

        if (! $isSqlite) {
            $this->fail(sprintf(
                'Tests are about to run against the "%s" connection on database "%s". '
                .'Refusing to continue: RefreshDatabase would drop its tables. '
                .'Check that phpunit.xml still pins DB_CONNECTION=sqlite and DB_DATABASE=:memory: '
                .'with force="true".',
                (string) $connection,
                (string) $database,
            ));
        }
    }
}
