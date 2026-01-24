<?php

use Core\Container\Container;

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

if (!getenv('JWT_SECRET')) {
    $secret = bin2hex(random_bytes(32));
    file_put_contents(__DIR__ . '/.env', "JWT_SECRET={$secret}\n", FILE_APPEND | LOCK_EX);
    putenv("JWT_SECRET={$secret}");
}

$container = new Container();

// Регистрируем PDO
$container->set(PDO::class, function () {
    $dsn = 'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') .
        ';port=' . ($_ENV['DB_PORT'] ?? 3306) .
        ';dbname=' . ($_ENV['DB_NAME'] ?? 'sonata');
    return new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
});

return $container;