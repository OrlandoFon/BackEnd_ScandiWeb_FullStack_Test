<?php

namespace Tests;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Config\Seeder;

/**
 * Shared setup utility for database-related tests.
 * This class initializes a file-based SQLite database and seeds it with test data.
 */
class TestSetup
{
    /**
     * Initializes a file-based SQLite database and configures the Doctrine EntityManager.
     *
     * @return EntityManager The configured Doctrine EntityManager.
     */
    public static function initializeEntityManager(): EntityManager
    {
        // Include Composer autoloader for dependency management
        require_once __DIR__ . '/../vendor/autoload.php';

        // Define paths
        $entityPath = dirname(__DIR__) . '/App/Entities';
        $dataDir = dirname(__DIR__) . '/data';
        $dbPath = $dataDir . '/test_db.sqlite';

        // Ensure data directory exists
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0775, true);
        }

        // Doctrine configuration for SQLite file-based database
        $config = Setup::createAttributeMetadataConfiguration(
            [$entityPath], // Path to entity classes
            true,          // Enable development mode for detailed error messages
            null,          // Proxy directory
            null,          // Cache (null uses default cache)
            false          // Use simple annotation reader
        );

        // SQLite connection parameters
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'path' => $dbPath, // Path to the SQLite file
        ];

        // Create EntityManager
        $entityManager = EntityManager::create($connectionParams, $config);

        return $entityManager;
    }

    /**
     * Populates the SQLite database with test data using the Seeder class.
     * Ensures that the database is clean before populating.
     *
     * @param EntityManager $entityManager The Doctrine EntityManager.
     * @return void
     *
     * @throws \Exception If seeding fails.
     */
    public static function populateDatabase(EntityManager $entityManager): void
    {
        // Call the Seeder class to handle the seeding process
        Seeder::seedDatabase($entityManager);
    }
}
