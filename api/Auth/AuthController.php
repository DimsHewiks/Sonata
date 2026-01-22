<?php

namespace Api\Auth;


use Core\Attributes\Controller;
use Core\Attributes\Route;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

#[Controller(prefix: '/api')]
class AuthController
{
    private const USERS = [
        'user@example.com' => 'password123'
    ];

    #[Route(path: '/login', method: 'POST')]
    public function login(): string
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!isset(self::USERS[$email]) || self::USERS[$email] !== $password) {
            http_response_code(401);
            return json_encode(['error' => 'Invalid credentials']);
        }

        $payload = [
            'iss' => 'sonata-fw',
            'sub' => $email,
            'iat' => time(),
            'exp' => time() + 3600 // токен живёт 1 час
        ];

        $jwt = JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
        return json_encode(['token' => $jwt]);
    }

    #[Route(path: '/profile', method: 'GET')]
    public function profile(): string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            return json_encode(['error' => 'Missing or invalid token']);
        }

        try {
            $token = $matches[1];
            $decoded = JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
            return json_encode(['user' => $decoded->sub, 'message' => 'Welcome to your profile!']);
        } catch (\Exception $e) {
            http_response_code(401);
            return json_encode(['error' => 'Invalid token']);
        }
    }
}