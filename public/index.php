<?php

// Define the base directory for the application
define('BASE_DIR', dirname(__DIR__));

// Define the logs directory
const LOGS_DIR = BASE_DIR . '/logs';

// Ensure logs directory exists with proper permissions
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0775, true);
}

// Define the specific log file for GraphQL
$logFile = LOGS_DIR . '/graphql.log';

// Autoload dependencies using Composer
require_once BASE_DIR . '/vendor/autoload.php';

// Add specific handler for errors
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logFile) {
    $message = date('Y-m-d H:i:s') . " [$errno] $errstr in $errfile:$errline\n";
    error_log($message, 3, $logFile);
    return true;
});

// Update exception handler to log uncaught exceptions
set_exception_handler(function (Throwable $e) use ($logFile) {
    $message = date('Y-m-d H:i:s') . " Uncaught Exception:\n";
    $message .= "Message: " . $e->getMessage() . "\n";
    $message .= "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $message .= "Trace:\n" . $e->getTraceAsString() . "\n";
    error_log($message, 3, $logFile);
});

// Log application start
try {
    error_log("GraphQL server started - " . date('Y-m-d H:i:s') . "\n", 3, $logFile);
} catch (Exception $e) {
    die("Failed to write to log file: " . $e->getMessage());
}

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
