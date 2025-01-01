<?php

use Dotenv\Dotenv;

// Load the .env file using vlucas/phpdotenv
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Return the database connection parameters using environment variables
return [
    'driver' => 'pdo_mysql', // Database driver
    'host' => $_ENV['DB_HOST'], // Database host 
    'dbname' => $_ENV['DB_NAME'], // Database name
    'user' => $_ENV['DB_USER'], // Database username
    'password' => $_ENV['DB_PASSWORD'], // Database password
    'charset' => 'utf8mb4', // Character set
];
