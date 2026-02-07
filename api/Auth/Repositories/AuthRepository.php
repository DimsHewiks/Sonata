<?php

namespace Api\Auth\Repositories;


use PDO;

class AuthRepository
{
    public function __construct(
        private PDO $pdo
    ) { }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT BIN_TO_UUID(uuid) AS uuid, email, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function createUser(string $uuid, string $email, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (uuid, email, password_hash) VALUES (UUID_TO_BIN(?), ?, ?)");
        $stmt->execute([$uuid, $email, $passwordHash]);
    }

    public function saveRefreshToken(string $userUuid, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO refresh_tokens (user_uuid, token_hash, expires_at)
            VALUES (UUID_TO_BIN(?), ?, ?)
        ");
        $stmt->execute([$userUuid, $tokenHash, $expiresAt]);
    }

    public function findActiveRefreshToken(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT rt.*, BIN_TO_UUID(u.uuid) AS user_uuid, u.email
            FROM refresh_tokens rt
            JOIN users u ON rt.user_uuid = u.uuid
            WHERE rt.token_hash = ?
              AND rt.revoked = 0
              AND rt.expires_at > NOW()
        ");
        $stmt->execute([$tokenHash]);
        return $stmt->fetch() ?: null;
    }

    public function revokeRefreshTokensByUserUuid(string $userUuid): void
    {
        $stmt = $this->pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE user_uuid = UUID_TO_BIN(?)");
        $stmt->execute([$userUuid]);
    }

    public function revokeRefreshTokenByHash(string $tokenHash): void
    {
        $stmt = $this->pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE token_hash = ?");
        $stmt->execute([$tokenHash]);
    }
}
