<?php

use Dotenv\Dotenv;

// Load the .env file using vlucas/phpdotenv
if (file_exists(dirname(__DIR__) . '/.env')) {
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
}

// Determine whether to use JawsDB or local database
if ($_ENV['USE_JAWSDB'] === '1') {
    // JawsDB configuration
    return [
        'driver'   => 'pdo_mysql',
        'host'     => $_ENV['DB_JAWS_HOST'],
        'port'     => $_ENV['DB_JAWS_PORT'],
        'dbname'   => $_ENV['DB_JAWS_NAME'],
        'user'     => $_ENV['DB_JAWS_USER'],
        'password' => $_ENV['DB_JAWS_PASSWORD'],
        'charset'  => 'utf8mb4',
    ];
} else {
    // Local database configuration
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
