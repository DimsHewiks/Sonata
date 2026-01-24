<?php

namespace Core\Cache;

class OpenApiCache
{
    private string $cacheFile;

    public function __construct(string $cacheDir = __DIR__ . '/../../var/cache')
    {
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $this->cacheFile = $cacheDir . '/openapi.cache.php';
    }

    public function get(): ?array
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }
        return include $this->cacheFile;
    }

    public function store(array $spec): void
    {
        file_put_contents($this->cacheFile, '<?php return ' . var_export($spec, true) . ';');
    }

    public function clear(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
}