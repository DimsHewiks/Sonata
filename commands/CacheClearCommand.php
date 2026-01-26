<?php

namespace Command;

class CacheClearCommand
{
    public function execute(): void
    {
        $cacheDir = __DIR__ . '/../var/cache/';

        if (!is_dir($cacheDir)) {
            echo "Cache directory does not exist.\n";
            return;
        }

        $files = glob($cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                echo "Deleted: $file\n";
            }
        }

        echo "Cache cleared successfully!\n";
    }
}