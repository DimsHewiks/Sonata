<?php

use Core\Container\Container;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
            $_ENV['DB_PORT'] ,
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
registerAutoServices($container, [
    __DIR__ . '/api',
    __DIR__ . '/view',
    __DIR__ . '/commands'
]);

return $container;


/**
 * Регистрирует все репозитории и сервисы
 */
function registerAutoServices(Container $container, array $directories): void
{
    foreach ($directories as $dir) {
        if (!is_dir($dir)) continue;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;

            $baseName = basename($dir);
            $relativePath = str_replace($dir . '/', '', $file->getPathname());
            $className = ucfirst($baseName) . '\\' . str_replace('/', '\\', substr($relativePath, 0, -4));

            // Регистрируем ТОЛЬКО контроллеры
            if (str_ends_with($className, 'Controller')) {
                $container->set($className);
            }
        }
    }

    $container->set(\Core\Service\ConfigService::class);
}