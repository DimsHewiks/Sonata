<?php

namespace Api\Instruments\Repositories;

use PDO;

class InstrumentRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function listAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, sticker FROM instruments ORDER BY id");
        return $stmt->fetchAll() ?: [];
    }

    public function create(string $name, ?string $sticker): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO instruments (name, sticker) VALUES (?, ?)");
        $stmt->execute([$name, $sticker]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, sticker FROM instruments WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT id, name, sticker FROM instruments WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll() ?: [];
    }

    public function findByUserUuid(string $userUuid): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.id, i.name, i.sticker
            FROM user_instruments ui
            JOIN instruments i ON ui.instrument_id = i.id
            WHERE ui.user_uuid = UUID_TO_BIN(?)
            ORDER BY i.id
        ");
        $stmt->execute([$userUuid]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int, int> $instrumentIds
     */
    public function setUserInstruments(string $userUuid, array $instrumentIds): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_instruments WHERE user_uuid = UUID_TO_BIN(?)");
            $stmt->execute([$userUuid]);

            if (!empty($instrumentIds)) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO user_instruments (user_uuid, instrument_id) VALUES (UUID_TO_BIN(?), ?)"
                );
                foreach ($instrumentIds as $instrumentId) {
                    $stmt->execute([$userUuid, $instrumentId]);
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
