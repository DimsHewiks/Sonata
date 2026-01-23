<?php
namespace Api;

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

            $payload = [
                'iss' => 'sonata-fw',
                'sub' => $user['id'],
                'email' => $user['email'],
                'iat' => time(),
                'exp' => time() + 3600
            ];

            $jwt = JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
            return json_encode(['token' => $jwt]);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            return json_encode(['error' => 'Login failed']);
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
}