<?php

namespace Api\Auth\Services;

use Api\Auth\Repositories\AuthRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    public function __construct(
        private AuthRepository $authRepository,
        private string $jwtSecret
    ) {}

    public function login(string $email, string $password): ?array
    {
        $user = $this->authRepository->findUserByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        $accessToken = $this->generateAccessToken($user['id'], $user['email']);
        $refreshToken = $this->createRefreshToken($user['id']);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];
    }

    public function register(string $email, string $password): void
    {
        if (empty($email) || empty($password)) {
            throw new \InvalidArgumentException('Email and password are required');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->authRepository->createUser($email, $hash);
    }

    public function refresh(string $refreshToken): ?array
    {
        $tokenHash = hash('sha256', $refreshToken);
        $record = $this->authRepository->findActiveRefreshToken($tokenHash);

        if (!$record) {
            return null;
        }

        // Отзываем старый
        $this->authRepository->revokeRefreshTokenById($record['id']);

        // Выдаём новый
        $newAccessToken = $this->generateAccessToken($record['user_id'], $record['email']);
        $newRefreshToken = $this->createRefreshToken($record['user_id']);

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];
    }

    public function logout(string $accessToken): void
    {
        try {
            $payload = JWT::decode($accessToken, new Key($this->jwtSecret, 'HS256'));
            $this->authRepository->revokeRefreshTokensByUserId((int)$payload->sub);
        } catch (\Exception $e) {
            // Игнорируем ошибки при логауте
        }
    }

    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    // --- Вспомогательные методы ---

    private function generateAccessToken(int $userId, string $email): string
    {
        $payload = [
            'iss' => 'sonata-fw',
            'sub' => $userId,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + 3600
        ];
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function createRefreshToken(int $userId): string
    {
        $refreshToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $refreshToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 3600));
        $this->authRepository->saveRefreshToken($userId, $tokenHash, $expiresAt);
        return $refreshToken;
    }
}