<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

error_log("Base URL: " . $_SERVER['REQUEST_URI']);
error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
require __DIR__.'/vendor/autoload.php';

$debug = getenv('APP_ENV') === 'development';

$router = new Core\Router($debug);
$router->registerControllers();

// Debug routes
$router->dispatch(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    $_SERVER['REQUEST_METHOD']
);