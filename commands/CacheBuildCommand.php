<?php

namespace Command;

use Sonata\Framework\Router;
use Sonata\Framework\Container\ContainerInterface;

class CacheBuildCommand
{
    public function execute(ContainerInterface $container): void
    {
        echo "📦 Building cache...\n";

        $basePath = dirname(__DIR__);
        $router = new Router($container, false, null, $basePath);
        $router->registerControllers();

        $routes = $router->getRoutes();

        $this->writeCacheFile('routes.php', $routes);

        echo "📦 Cache built successfully!\n";
    }

    private function writeCacheFile(string $filename, array $data): void
    {
        $cacheDir = __DIR__ . '/../var/cache/';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        if (!is_writable($cacheDir)) {
            throw new \RuntimeException("📦 Cache directory is not writable: $cacheDir");
        }

        $filePath = $cacheDir . $filename;
        $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";

        // Атомарная запись
        $tempFile = $filePath . '.tmp';
        file_put_contents($tempFile, $content);
        rename($tempFile, $filePath);

        echo "📦 Wrote cache: $filename\n";
    }
}
