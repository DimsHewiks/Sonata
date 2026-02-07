#!/usr/bin/env php
<?php
define('SONATA_BOOTSTRAP_NO_COMMANDS', true);

require __DIR__ . '/../vendor/autoload.php';

$command = $argv[1] ?? null;

switch ($command) {
    case 'cache:clear':
        (new Sonata\Framework\Cache\RoutesCache())->clear();
        echo "Routes cache cleared!\n";
        break;
    case 'jwt:install':
        $container = require __DIR__ . '/../bootstrap.php';
        $pdo = $container->get(PDO::class);
        (new Sonata\JwtAuth\Migrations\JwtMigrator())->install($pdo);
        echo "JWT migrations installed!\n";
        break;
    default:
        echo "Available commands:\n cache:clear\n jwt:install\n";
}
