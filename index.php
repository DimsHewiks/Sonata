<?php
// Включение отображения ошибок
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__.'/vendor/autoload.php';

error_log("Base URL: " . $_SERVER['REQUEST_URI']);
error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);

$router = new Core\Router();
$router->registerControllers(__DIR__.'/api');
$router->registerControllers(__DIR__.'/view');

// Debug routes
$router->dispatch(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    $_SERVER['REQUEST_METHOD']
);