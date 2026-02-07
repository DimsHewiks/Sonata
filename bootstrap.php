<?php

use Sonata\Framework\Container\Container;
use Sonata\Framework\ControllerFinder;
use Sonata\Framework\Routing\ControllerDirectoryResolver;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$_ENV['SONATA_BASE_PATH'] = $_ENV['SONATA_BASE_PATH'] ?? __DIR__;
putenv('SONATA_BASE_PATH=' . $_ENV['SONATA_BASE_PATH']);

if (!getenv('JWT_SECRET')) {
    $secret = bin2hex(random_bytes(32));
    file_put_contents(__DIR__ . '/.env', "JWT_SECRET={$secret}\n", FILE_APPEND | LOCK_EX);
    putenv("JWT_SECRET={$secret}");
}

$container = new Container();

$container->set(ValidatorInterface::class, static function () {
    return Validation::createValidatorBuilder()
        ->enableAttributeMapping()
        ->getValidator();
});

$container->set(PDO::class, static function (): PDO {
    return new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME']
        ),
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
});

/**
 * Регистрация контролеров и сервисов
 */
registerAutoServices($container, __DIR__);

if (!defined('SONATA_BOOTSTRAP_NO_COMMANDS')) {
    // Запуск команды cache:build
    if (($_SERVER['argv'][1] ?? null) === 'cache:build') {
        $command = new \Command\CacheBuildCommand();
        $command->execute($container);
        exit;
    }

    if (($_SERVER['argv'][1] ?? null) === 'cache:clear') {
        $command = new \Command\CacheClearCommand();
        $command->execute();
        exit;
    }
}

return $container;

/**
 * Регистрирует все репозитории и сервисы
 */
function registerAutoServices(Container $container, string $basePath): void
{
    $finder = new ControllerFinder();
    $directories = ControllerDirectoryResolver::resolve($basePath);

    foreach ($directories as $dir) {
        foreach ($finder->find($dir) as $controller) {
            $container->set($controller);
        }
    }

    $container->set(\Sonata\Framework\Service\ConfigService::class);
}
