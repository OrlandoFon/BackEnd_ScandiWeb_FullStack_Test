<?php

use Dotenv\Dotenv;
use FastRoute\RouteCollector;
use Config\Bootstrap;
use Tests\TestSetup;

define('BASE_DIR', dirname(__DIR__));
const LOGS_DIR = BASE_DIR . '/logs';

// Ensure the logs directory exists
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0775, true);
}

$logFile = LOGS_DIR . '/graphql.log';

require_once BASE_DIR . '/vendor/autoload.php';

// Setup error and exception handlers
setupErrorHandlers($logFile);

// Load environment variables
if (file_exists(BASE_DIR . '/.env')) {
    $dotenv = Dotenv::createImmutable(BASE_DIR);
    $dotenv->load();
}

// Handle CORS
cors([$_ENV['FRONTEND_URL']]);

// Determine environment and setup EntityManager
$isTesting = $_ENV['TESTING'] ?? '0';
$entityManager = setupEntityManager($isTesting);

// Setup routing
$dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->post('/graphql', [\App\Controllers\GraphQL::class, 'handle']);
});

// Handle HTTP request
handleRequest($dispatcher, $entityManager);

/**
 * Setup error and exception handlers.
 */
function setupErrorHandlers(string $logFile): void
{
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
}

/**
 * Setup CORS headers.
 */
function cors(array $allowedOrigins): void
{
    // Check if the request's origin is allowed
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $origin = $_SERVER['HTTP_ORIGIN'];

        // Ensure the origin is valid and matches HTTPS
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Max-Age: 86400"); // Cache for 1 day
        }
    }

    // Handle preflight OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        }
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        http_response_code(204); // No Content
        exit;
    }

    // Ensure CORS headers are sent with the actual response as well
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header("Access-Control-Allow-Credentials: true");
    }
}


/**
 * Setup the EntityManager based on the environment.
 */
function setupEntityManager(string $isTesting)
{
    if ($isTesting === '1') {
        error_log('[index.php] Test environment detected, using SQLite database');
        static $entityManager = null;
        if ($entityManager === null) {
            $entityManager = TestSetup::initializeEntityManager();
            \App\Controllers\GraphQL::setEntityManager($entityManager);
        }
    } else {
        error_log('[index.php] Production environment detected, using MySQL database');
        $dbParams = require BASE_DIR . '/config/db_params.php';
        $entityManager = Bootstrap::initEntityManager($dbParams);
        \App\Controllers\GraphQL::setEntityManager($entityManager);
    }

    return $entityManager;
}

/**
 * Handle the HTTP request.
 */
function handleRequest($dispatcher, $entityManager): void
{
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
}

