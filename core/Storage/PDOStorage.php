<?php

namespace Core\Storage;

class PDOStorage
{
    public function __construct(
        protected \PDO $pdo
    ) {}

    protected function getPdo(): \PDO
    {
        return $this->pdo;
    }
}