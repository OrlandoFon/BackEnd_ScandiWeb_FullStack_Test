<?php

// Define the base directory
define('BASE_DIR', dirname(__DIR__));

// Autoload dependencies
require_once BASE_DIR . '/vendor/autoload.php';

// Load the Bootstrap class
use Config\Bootstrap;

// Initialize the EntityManager
$entityManager = Bootstrap::initEntityManager(require BASE_DIR . '/config/db_params.php');

// Initialize the router
$dispatcher = Bootstrap::initRoutes();

// Handle the request
$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(['error' => '404 Not Found']);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(['error' => '405 Method Not Allowed']);
        break;

    case FastRoute\Dispatcher::FOUND:
        [$class, $method] = $routeInfo[1];
        if ($class === \App\Controllers\GraphQL::class) {
            \App\Controllers\GraphQL::setEntityManager($entityManager);
            echo $class::$method();
        } else {
            echo (new $class($entityManager))->$method();
        }
        break;

    default:
        http_response_code(500);
        echo json_encode(['error' => '500 Internal Server Error']);
        break;
}
