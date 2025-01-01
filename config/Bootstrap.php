<?php

namespace Config;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;

class Bootstrap
{
    private static ?EntityManager $entityManager = null;

    /**
     * Initializes the Doctrine EntityManager.
     *
     * @param array $dbParams Database connection parameters.
     * @return EntityManager The initialized EntityManager.
     */
    public static function initEntityManager(array $dbParams): EntityManager
    {
        if (self::$entityManager === null) {
            $config = ORMSetup::createAttributeMetadataConfiguration(
                [dirname(__DIR__) . '/App/Entities'], // Fix path to entities
                true
            );
            self::$entityManager = EntityManager::create($dbParams, $config);
        }
        return self::$entityManager;
    }

    /**
     * Initializes the FastRoute dispatcher.
     *
     * @return Dispatcher The configured dispatcher.
     */
    public static function initRoutes(): Dispatcher
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            // Define application routes here
            $r->post('/graphql', [\App\Controllers\GraphQL::class, 'handle']);
        });
    }
}
