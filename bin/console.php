#!/usr/bin/env php
<?php
require __DIR__.'/../vendor/autoload.php';

$command = $argv[1] ?? null;

switch ($command) {
    case 'cache:clear':
        (new Core\Cache\RoutesCache())->clear();
        echo "Routes cache cleared!\n";
        break;
    default:
        echo "Available commands:\n cache:clear\n";
}