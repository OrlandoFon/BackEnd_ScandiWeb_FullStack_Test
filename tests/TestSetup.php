<?php

namespace Tests;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Config\Seeder;

use Dotenv\Dotenv;

// Load the .env file using vlucas/phpdotenv
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/**
 * Shared setup utility for database-related tests.
 * This class initializes a file-based SQLite database and seeds it with test data.
 */
class TestSetup
{
    /**
     * Initializes a file-based SQLite database and configures the Doctrine EntityManager.
     * Skips tests if TESTING=0 in the environment.
     *
     * @return EntityManager The configured Doctrine EntityManager.
     *
     * @throws \RuntimeException If testing is disabled via environment.
     */
    public static function initializeEntityManager(): EntityManager
    {
        // Skip tests if TESTING=0
        if ($_ENV['TESTING'] === '0') {
            throw new \RuntimeException("Tests are disabled because TESTING=0.");
        }

        // Include Composer autoloader
        require_once __DIR__ . '/../vendor/autoload.php';

        // Paths
        $entityPath = dirname(__DIR__) . '/App/Entities';
        $dataDir = dirname(__DIR__) . '/data';
        $dbPath = $dataDir . '/test_db.sqlite';

        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0775, true);
        }

        // Doctrine configuration for SQLite
        $config = Setup::createAttributeMetadataConfiguration(
            [$entityPath],
            true,
            null,
            null,
            false
        );

        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'path' => $dbPath,
        ];

        // Create EntityManager
        return EntityManager::create($connectionParams, $config);
    }

    /**
     * Populates the SQLite database with test data using the Seeder class.
     * Ensures a clean database before populating.
     *
     * @param EntityManager $entityManager The Doctrine EntityManager.
     * @return void
     */
    public static function populateDatabase(EntityManager $entityManager): void
    {
        Seeder::seedDatabase($entityManager);
    }
}
