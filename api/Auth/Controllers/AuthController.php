<?php

namespace Api\Auth\Controllers;

use Api\Auth\DTOs\Request\RegistDTO;
use Api\Auth\Services\AuthService;
use Core\Attributes\Controller;
use Core\Attributes\From;
use Core\Attributes\Route;
use Core\Attributes\Tag;
use Core\Http\Response;

#[Controller(prefix: '/api')]
#[Tag('Регистрация/Авторизация')]
class AuthController
{
    public function __construct(private AuthService $authService) {}

    #[Route(path: '/login', method: 'POST', summary: 'Вход', description: 'Метод входа в систему')]
    public function login(#[From('json')] RegistDTO $dto): never
    {
        try {
            $tokens = $this->authService->login($dto->email, $dto->password);
            if (!$tokens) {
                Response::error('Invalid credentials', 401);
            }
            Response::json($tokens, 200);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error(
                'Login failed',
                500,
                $e->getMessage()
            );
        }
    }

    #[Route(path: '/me', method: 'GET', summary: 'Профиль', description: 'Получение информации об авторизированном пользователе')]
    public function profile(): string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            return json_encode(['error' => 'Missing token']);
        }

        $payload = $this->authService->validateToken($matches[1]);
        if (!$payload) {
            http_response_code(401);
            return json_encode(['error' => 'Invalid token']);
        }

        return json_encode([
            'user_id' => $payload->sub,
            'email' => $payload->email,
            'message' => 'Authenticated!'
        ]);
    }

    #[Route(path: '/registration', method: 'POST', summary: 'Регистрация', description: 'Метод регистрации нового юзера')]
    public function createAccount(): string
    {
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $this->authService->register($input['email'] ?? '', $input['password'] ?? '');
            return json_encode(['msg' => 'Успешная регистрация']);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            return json_encode(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            return json_encode(['error' => 'Registration failed']);
        }
    }

    #[Route(path: '/refresh', method: 'POST', summary: 'обновление рефреша', description: 'Метод для обновления рефреша юзера')]
    public function refresh(): string
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $refreshToken = $input['refresh_token'] ?? '';

        try {
            $tokens = $this->authService->refresh($refreshToken);
            if (!$tokens) {
                http_response_code(401);
                return json_encode(['error' => 'Invalid or expired refresh token']);
            }
            return json_encode($tokens);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            return json_encode(['error' => 'Token refresh failed']);
        }
    }

    #[Route(path: '/logout', method: 'POST', summary: 'Выход из системы', description: 'Метод входа в систему')]
    public function logout(): string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/', $authHeader, $matches)) {
            $this->authService->logout($matches[1]);
        }
        return json_encode(['message' => 'Logged out']);
    }
}