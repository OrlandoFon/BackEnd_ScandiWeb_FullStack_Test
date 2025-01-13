<?php

use Dotenv\Dotenv;

// Load .env
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

// Determine whether to use non-local database or local
if (isset($_ENV['USE_NONLOCALDB']) && $_ENV['USE_NONLOCALDB'] === '1') {
    // Non-local database config
    return [
        'driver'   => 'pdo_mysql',
        'host'     => $_ENV['DB_NONLOCALDB_HOST'],
        'port'     => $_ENV['DB_NONLOCALDB_PORT'],
        'dbname'   => $_ENV['DB_NONLOCALDB_NAME'],
        'user'     => $_ENV['DB_NONLOCALDB_USER'],
        'password' => $_ENV['DB_NONLOCALDB_PASSWORD'],
        'charset'  => 'utf8mb4',
    ];
} else {
    // Local database
    return [
        'driver'   => 'pdo_mysql',
        'host'     => $_ENV['DB_HOST'],
        'port'     => $_ENV['DB_PORT'],
        'dbname'   => $_ENV['DB_NAME'],
        'user'     => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
        'charset'  => 'utf8mb4',
    ];
}
