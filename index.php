<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

error_log("Base URL: " . $_SERVER['REQUEST_URI']);
error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);

require __DIR__.'/vendor/autoload.php';

$envPath = __DIR__.'/';
try {
    $dotenv = Dotenv\Dotenv::createImmutable($envPath);
    $dotenv->safeLoad();
} catch (Dotenv\Exception\InvalidPathException $e) {
    die('Please create .env file. Copy from .env.example');
}

$debug = $_ENV['APP_ENV'] === 'dev';
$router = new Core\Router($debug);
$router->registerControllers();

$router->dispatch(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    $_SERVER['REQUEST_METHOD']
);