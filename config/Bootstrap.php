<?php

namespace Config;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

/**
 * Class Bootstrap
 *
 * Initializes and provides a singleton instance of Doctrine's EntityManager.
 */
class Bootstrap
{
    /**
     * @var EntityManager|null Singleton instance of EntityManager.
     */
    private static ?EntityManager $entityManager = null;

    /**
     * Initializes the EntityManager with the provided database parameters.
     *
     * @param array $dbParams Database connection parameters.
     * @return EntityManager The initialized EntityManager.
     */
    public static function initEntityManager(array $dbParams): EntityManager
    {
        // Check if the EntityManager has already been initialized
        if (self::$entityManager === null) {
            // Configure Doctrine ORM with entity paths and development mode
            $config = ORMSetup::createAttributeMetadataConfiguration(
                [dirname(__DIR__) . '/App/Entities'],
                true // Enable development mode for detailed error messages
            );

            // Create the EntityManager instance
            self::$entityManager = EntityManager::create($dbParams, $config);
            error_log('[Bootstrap] Created EntityManager with production DB params');
        }

        // Return the singleton EntityManager instance
        return self::$entityManager;
    }
}
