<?php

namespace Api\Auth\Repositories;


use PDO;

class AuthRepository
{
    public function __construct(
        private PDO $pdo
    ) { }

    public function findUserByLoginOrEmail(string $identifier): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT BIN_TO_UUID(uuid) AS uuid, login, email, password_hash
            FROM users
            WHERE login = ? OR email = ?
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch() ?: null;
    }

    public function createUser(
        string $uuid,
        string $name,
        int $age,
        string $login,
        ?string $email,
        string $passwordHash
    ): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (uuid, name, age, login, email, password_hash)
            VALUES (UUID_TO_BIN(?), ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$uuid, $name, $age, $login, $email, $passwordHash]);
    }

    public function createUserAvatar(string $userUuid, array $avatar, int $status): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users_avatars (
                user_uuid,
                original_name,
                saved_name,
                full_path,
                relative_path,
                size,
                extension,
                uploaded,
                errors,
                status
            ) VALUES (
                UUID_TO_BIN(?),
                ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $errors = $avatar['errors'] ?? null;
        if (is_array($errors)) {
            $errors = json_encode($errors, JSON_UNESCAPED_UNICODE);
        }

        $stmt->execute([
            $userUuid,
            (string)($avatar['original_name'] ?? ''),
            (string)($avatar['saved_name'] ?? ''),
            (string)($avatar['full_path'] ?? ''),
            (string)($avatar['relative_path'] ?? ''),
            (int)($avatar['size'] ?? 0),
            (string)($avatar['extension'] ?? ''),
            !empty($avatar['uploaded']) ? 1 : 0,
            $errors,
            $status
        ]);
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

    public function findUserProfileByUuid(string $userUuid): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                BIN_TO_UUID(u.uuid) AS uuid,
                u.name,
                u.age,
                u.login,
                u.email,
                (
                    SELECT ua.relative_path
                    FROM users_avatars ua
                    WHERE ua.user_uuid = u.uuid
                      AND ua.status = 1
                    ORDER BY ua.created_at DESC
                    LIMIT 1
                ) AS avatar_path
            FROM users u
            WHERE u.uuid = UUID_TO_BIN(?)
            LIMIT 1
        ");
        $stmt->execute([$userUuid]);
        return $stmt->fetch() ?: null;
    }
}
