<?php

// Autoload dependencies using Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Import the Seeder and Bootstrap classes from the Config namespace
use Config\Seeder;
use Config\Bootstrap;

// Load database configuration parameters from the db_params.php file
$dbParams = require __DIR__ . '/../config/db_params.php';

// Initialize the Doctrine EntityManager using the Bootstrap class and the loaded database parameters
$entityManager = Bootstrap::initEntityManager($dbParams);

try {
    // Execute the database seeding process using the Seeder class
    Seeder::seedDatabase($entityManager);
} catch (Exception $e) {
    // If an exception occurs during seeding, output an error message with the exception details
    echo "Seeding failed: " . $e->getMessage() . "\n";

    // Terminate the script with a non-zero exit code to indicate failure
    exit(1);
}
