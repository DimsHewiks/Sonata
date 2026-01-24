<?php
namespace Api\Auth\Controllers;

use Core\Attributes\Controller;
use Core\Attributes\Route;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

#[Controller(prefix: '/api')]
class AuthController
{
    private function getDb(): \PDO
    {
        $host = getenv('DB_HOST') ?: 'mariadb';
        $dbname = getenv('DB_NAME') ?: 'sonata';
        $user = getenv('DB_USER') ?: 'appuser';
        $pass = getenv('DB_PASS') ?: 'apppass';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        return new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
    }

    #[Route(path: '/login', method: 'POST')]
    public function login(): string
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        try {
            $stmt = $this->getDb()->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                http_response_code(401);
                return json_encode(['error' => 'Invalid credentials']);
            }

            $accessPayload = [
                'iss' => 'sonata-fw',
                'sub' => $user['id'],
                'email' => $user['email'],
                'iat' => time(),
                'exp' => time() + 3600
            ];
            $accessToken = JWT::encode($accessPayload, getenv('JWT_SECRET'), 'HS256');

            $refreshToken = bin2hex(random_bytes(32));
            $refreshTokenHash = hash('sha256', $refreshToken);
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 3600)); // 30 дней

            $stmt = $this->getDb()->prepare("
                INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $refreshTokenHash, $expiresAt]);

            return json_encode([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ]);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            return json_encode([
                'error' => 'Login failed',
                'E' => $e->getMessage()
            ]);
        }
    }

    #[Route(path: '/profile', method: 'GET')]
    public function profile(): string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            return json_encode(['error' => 'Missing token']);
        }

        try {
            $token = $matches[1];
            $decoded = JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));

            return json_encode([
                'user_id' => $decoded->sub,
                'email' => $decoded->email,
                'message' => 'Authenticated!'
            ]);

        } catch (\Exception $e) {
            http_response_code(401);
            return json_encode(['error' => 'Invalid token']);
        }
    }

    #[Route(path: '/reg', method: 'POST')]
    public function createAccount(): string
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'];
        $password = $input['password'];

        if (empty($email) || empty($password)) {
            http_response_code(400);
            return json_encode(['error' => 'Переданы не все поля']);
        }

        $stmt = $this->getDb()->prepare(/**@lang MariaDB*/"
            INSERT INTO
                users (email, password_hash) 
            VALUES (?, ?)"
        );

        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);

        return json_encode([
            'msg' => 'Успешная регистрация'
        ]);
    }

    #[Route(path: '/refresh', method: 'POST')]
    public function refresh(): string
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $refreshToken = $input['refresh_token'] ?? '';

        if (!$refreshToken) {
            http_response_code(400);
            return json_encode(['error' => 'Missing refresh_token']);
        }

        try {
            $tokenHash = hash('sha256', $refreshToken);

            // Найти активный refresh-токен
            $stmt = $this->getDb()->prepare("
            SELECT rt.*, u.id AS user_id, u.email
            FROM refresh_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.token_hash = ?
              AND rt.revoked = 0
              AND rt.expires_at > NOW()
        ");
            $stmt->execute([$tokenHash]);
            $record = $stmt->fetch();

            if (!$record) {
                http_response_code(401);
                return json_encode(['error' => 'Invalid or expired refresh token']);
            }

            // === Выдать новый access token ===
            $newAccessPayload = [
                'iss' => 'sonata-fw',
                'sub' => $record['user_id'],
                'email' => $record['email'],
                'iat' => time(),
                'exp' => time() + 3600
            ];
            $newAccessToken = JWT::encode($newAccessPayload, getenv('JWT_SECRET'), 'HS256');

            $newRefreshToken = bin2hex(random_bytes(32));
            $newRefreshTokenHash = hash('sha256', $newRefreshToken);
            $newExpiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 3600));

            $this->getDb()->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?")
                ->execute([$record['id']]);

            $this->getDb()->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)
        ")->execute([$record['user_id'], $newRefreshTokenHash, $newExpiresAt]);

            return json_encode([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ]);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            return json_encode(['error' => 'Token refresh failed']);
        }
    }

    #[Route(path: '/logout', method: 'POST')]
    public function logout(): string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/', $authHeader, $matches)) {
            $accessToken = $matches[1];
            try {
                $payload = JWT::decode($accessToken, new Key(getenv('JWT_SECRET'), 'HS256'));
                $this->getDb()->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE user_id = ?")
                    ->execute([$payload->sub]);
            } catch (\Exception $e) {
            }
        }
        return json_encode(['message' => 'Logged out']);
    }

    protected function getCurrentUserId(): ?int
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
            return null;
        }

        try {
            $key = new \Firebase\JWT\Key(getenv('JWT_SECRET'), 'HS256');
            $payload = \Firebase\JWT\JWT::decode($matches[1], $key);
            return (int)$payload->sub;
        } catch (\Exception $e) {
            return null;
        }
    }
}