<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (getenv('APP_ENV') === 'dev') {
    error_log("Base URL: " . ($_SERVER['REQUEST_URI'] ?? ''));
    error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? ''));
}

$container = require __DIR__ . '/bootstrap.php';

$debug = getenv('APP_ENV') === 'dev';

$router = new Sonata\Framework\Router($container, $debug, null, __DIR__);
$router->addMiddleware(\Api\Common\Middleware\AuthMiddleware::class);

$router->loadCachedRoutes();

if (empty($router->getRoutes())) {
    $router->registerControllers();
}

$router->dispatch(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/',
    $_SERVER['REQUEST_METHOD']
);
