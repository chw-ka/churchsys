<?php

/*
|--------------------------------------------------------------------------
| PHPUnit bootstrap
|--------------------------------------------------------------------------
|
| This file exists to make the test environment deterministic.
|
| The production containers export real environment variables — APP_ENV=production,
| DB_CONNECTION=mysql, DB_DATABASE=churchsys (see docker-compose.yml). Laravel
| resolves configuration through env(), which prefers those variables, and
| PHPUnit's <env> entries in phpunit.xml were not overriding them in practice.
| The result was that `php artisan test` inside the app container connected the
| suite to the LIVE church database. RefreshDatabase calls migrate:fresh, so that
| is a destructive accident waiting to happen.
|
| PHPUnit runs this bootstrap before any Laravel application is created, so the
| values below are written to putenv(), $_ENV and $_SERVER before env() is ever
| consulted. bootstrap/app.php loads .env as *immutable*, meaning it will not
| overwrite variables that are already set — so these win.
|
| tests/TestCase.php adds a final guard: it aborts the run if the resolved
| database is not SQLite.
|
*/

require __DIR__.'/../vendor/autoload.php';

$forced = [
    'APP_ENV'                => 'testing',
    'APP_DEBUG'              => 'true',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS'          => '4',
    'BROADCAST_CONNECTION'   => 'null',
    'CACHE_STORE'            => 'array',
    'DB_CONNECTION'          => 'sqlite',
    'DB_DATABASE'            => ':memory:',
    'DB_URL'                 => '',
    'DB_HOST'                => '127.0.0.1',
    'DB_PORT'                => '3306',
    'DB_USERNAME'            => 'root',
    'DB_PASSWORD'            => '',
    'MAIL_MAILER'            => 'array',
    'QUEUE_CONNECTION'       => 'sync',
    'SESSION_DRIVER'         => 'array',
];

foreach ($forced as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
