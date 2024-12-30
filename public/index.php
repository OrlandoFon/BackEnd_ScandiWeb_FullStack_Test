<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload Composer dependencies

// Define the routes using FastRoute
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    // Define a POST route for the /graphql endpoint
    $r->post('/graphql', [App\Controllers\GraphQL::class, 'handle']);
});

// Dispatch the current HTTP request
$routeInfo = $dispatcher->dispatch(
    $_SERVER['REQUEST_METHOD'], // The HTTP method used (GET, POST, etc.)
    $_SERVER['REQUEST_URI']    // The requested URI
);

// Handle the routing result
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // If no route matches the request, return a 404 Not Found
        http_response_code(404);
        echo json_encode(['error' => '404 Not Found']);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // If the HTTP method is not allowed for the route, return a 405 Method Not Allowed
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo json_encode(['error' => '405 Method Not Allowed', 'allowed' => $allowedMethods]);
        break;

    case FastRoute\Dispatcher::FOUND:
        // If the route matches, call the corresponding handler
        [$class, $method] = $routeInfo[1]; // The handler is defined as [ClassName, methodName]
        echo (new $class)->$method(); // Instantiate the class and call the method
        break;
}
