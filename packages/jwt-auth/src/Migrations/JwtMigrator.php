<?php

namespace Sonata\JwtAuth\Migrations;

use PDO;

class JwtMigrator
{
    public function install(PDO $pdo): void
    {
        $sqlFile = dirname(__DIR__, 2) . '/migrations/001_create_users_and_refresh_tokens.sql';
        if (!is_file($sqlFile)) {
            throw new \RuntimeException("Migration file not found: {$sqlFile}");
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new \RuntimeException("Failed to read migration file: {$sqlFile}");
        }

        foreach ($this->splitStatements($sql) as $statement) {
            $pdo->exec($statement);
        }
    }

    /**
     * @return string[]
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        foreach (explode(';', $sql) as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }
        return $statements;
    }
}
