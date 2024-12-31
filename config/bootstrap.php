<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

require_once __DIR__ . '/../vendor/autoload.php';

$configParams = require __DIR__ . '/config.php';

// Change to createAttributeMetadataConfiguration
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../App/Entities'],
    isDevMode: true,
);

$connection = DriverManager::getConnection([
    'driver'   => 'pdo_mysql',
    'host'     => $configParams['db']['host'],
    'dbname'   => $configParams['db']['name'],
    'user'     => $configParams['db']['user'],
    'password' => $configParams['db']['password'],
], $config);

$entityManager = new EntityManager($connection, $config);

try {
    $connection->connect();
    echo "Database connection successful!\n";
} catch (\Exception $e) {
    echo "Error connecting to the database: " . $e->getMessage() . "\n";
    exit(1);
}

return $entityManager;
