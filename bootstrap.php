<?php
require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Генерация JWT_SECRET, если не задан
if (!getenv('JWT_SECRET')) {
    $secret = bin2hex(random_bytes(32));
    file_put_contents(__DIR__ . '/.env', "JWT_SECRET={$secret}\n", FILE_APPEND | LOCK_EX);
    putenv("JWT_SECRET={$secret}");
}