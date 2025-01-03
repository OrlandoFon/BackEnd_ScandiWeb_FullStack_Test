<?php

use Dotenv\Dotenv;
use FastRoute\RouteCollector;
use Config\Bootstrap;
use Tests\TestSetup;

define('BASE_DIR', dirname(__DIR__));
const LOGS_DIR = BASE_DIR . '/logs';

// Setup logging
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0775, true);
}
$logFile = LOGS_DIR . '/graphql.log';

require_once BASE_DIR . '/vendor/autoload.php';

// Error handlers setup
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logFile) {
    $message = date('Y-m-d H:i:s') . " [$errno] $errstr in $errfile:$errline\n";
    error_log($message, 3, $logFile);
    return true;
});

set_exception_handler(function (Throwable $e) use ($logFile) {
    $message = date('Y-m-d H:i:s') . " Uncaught Exception:\n";
    $message .= "Message: " . $e->getMessage() . "\n";
    $message .= "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $message .= "Trace:\n" . $e->getTraceAsString() . "\n";
    error_log($message, 3, $logFile);
});

// Load environment variables
$dotenv = Dotenv::createImmutable(BASE_DIR);
$dotenv->load();

// Determine environment and setup EntityManager
$isTesting = $_ENV['TESTING'] ?? '0';

if ($isTesting === '1') {
    error_log('[index.php] Test environment detected, using file-based SQLite database');
    // Initialize EntityManager once
    static $entityManager = null;
    if ($entityManager === null) {
        $entityManager = TestSetup::initializeEntityManager();
        // Set EntityManager in GraphQL controller
        \App\Controllers\GraphQL::setEntityManager($entityManager);
    }
} else {
    error_log('[index.php] Production environment detected, using MySQL database');
    $dbParams = require BASE_DIR . '/config/db_params.php';
    $entityManager = Bootstrap::initEntityManager($dbParams);
    // Set EntityManager in GraphQL controller
    \App\Controllers\GraphQL::setEntityManager($entityManager);
}

// Setup routing
$dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->post('/graphql', [\App\Controllers\GraphQL::class, 'handle']);
});

// Handle request
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(['error' => '404 Not Found']);
        break;

    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(['error' => '405 Method Not Allowed']);
        break;

    case \FastRoute\Dispatcher::FOUND:
        [$class, $method] = $routeInfo[1];
        if ($class === \App\Controllers\GraphQL::class) {
            echo $class::$method();
        } else {
            echo (new $class($entityManager))->$method();
        }
        break;
}
