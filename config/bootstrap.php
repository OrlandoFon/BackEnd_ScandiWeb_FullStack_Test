<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../vendor/autoload.php';

$configParams = require __DIR__ . '/config.php';

$config = ORMSetup::createAttributeMetadataConfiguration(
    [__DIR__ . '/../App/Entities'],
    true,
    null,
    null,
    false
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

    // Create schema tool
    $schemaTool = new SchemaTool($entityManager);
    $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

    // Update schema if needed
    $schemaTool->updateSchema($metadata);

    echo "Database connection successful!\n";
} catch (\Exception $e) {
    echo "Error connecting to the database: " . $e->getMessage() . "\n";
    exit(1);
}

return $entityManager;
