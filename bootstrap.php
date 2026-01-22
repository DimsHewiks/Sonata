<?php
// bootstrap.php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); // не падает, если .env нет

// Генерация JWT_SECRET, если не задан
if (!getenv('JWT_SECRET')) {
    $secret = bin2hex(random_bytes(32));
    file_put_contents(__DIR__ . '/.env', "JWT_SECRET={$secret}\n", FILE_APPEND | LOCK_EX);
    putenv("JWT_SECRET={$secret}");
}