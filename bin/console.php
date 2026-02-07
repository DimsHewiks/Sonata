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
    default:
        echo "Available commands:\n cache:clear\n";
}
