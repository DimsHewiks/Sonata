<?php

namespace Api\Auth\Repositories;

use Core\Container\ContainerInterface;
use PDO;

class AuthRepository
{
    private PDO $pdo;

    public function __construct(ContainerInterface $container)
    {
        $this->pdo = $container->get(PDO::class);
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function createUser(string $email, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
        $stmt->execute([$email, $passwordHash]);
    }

    public function saveRefreshToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $tokenHash, $expiresAt]);
    }

    public function findActiveRefreshToken(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT rt.*, u.id AS user_id, u.email
            FROM refresh_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.token_hash = ?
              AND rt.revoked = 0
              AND rt.expires_at > NOW()
        ");
        $stmt->execute([$tokenHash]);
        return $stmt->fetch() ?: null;
    }

    public function revokeRefreshTokensByUserId(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    public function revokeRefreshTokenById(int $tokenId): void
    {
        $stmt = $this->pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?");
        $stmt->execute([$tokenId]);
    }
}