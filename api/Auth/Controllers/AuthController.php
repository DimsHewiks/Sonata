<?php

namespace Api\Auth\Controllers;

use Api\Auth\DTOs\Request\RefreshDto;
use Api\Auth\DTOs\Request\RegistDTO;
use Api\Auth\Services\AuthService;
use Core\Attributes\Controller;
use Core\Attributes\From;
use Core\Attributes\Inject;
use Core\Attributes\Route;
use Core\Attributes\Tag;
use Core\Http\Response;

#[Controller(prefix: '/api')]
#[Tag('Регистрация/Авторизация')]
class AuthController
{
    public function __construct(
        #[Inject] private AuthService $authService
    ) {}

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
    public function profile(): never
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);

            Response::error('Missing token');
        }

        $payload = $this->authService->validateToken($matches[1]);
        if (!$payload) {
            http_response_code(401);

            Response::error('Invalid token');
        }

        Response::json([
            'user_id' => $payload->sub,
            'email' => $payload->email,
            'message' => 'Authenticated!'
        ], 200);
    }

    #[Route(path: '/registration', method: 'POST', summary: 'Регистрация', description: 'Метод регистрации нового юзера')]
    public function createAccount(): never
    {
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $this->authService->register($input['email'] ?? '', $input['password'] ?? '');
            Response::json([
                'msg' => 'Успешная регистрация'
            ]);
        } catch (\InvalidArgumentException $e) {
            Response::error(
                $e->getMessage(),
                400
            );
        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error(
                'Registration failed',
                500
            );
        }
    }

    #[Route(path: '/refresh', method: 'POST', summary: 'обновление рефреша', description: 'Метод для обновления рефреша юзера')]
    public function refresh(
        #[From('json')] RefreshDto $dto
    ): never
    {
        try {
            $tokens = $this->authService->refresh(
                $dto->refreshToken
            );

            if (!$tokens) {
                Response::error(
                    'Invalid or expired refresh token',
                    401
                );
            }

            Response::json($tokens);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error('Token refresh failed', 500);
        }
    }

    #[Route(path: '/logout', method: 'POST', summary: 'Выход из системы', description: 'Метод входа в систему')]
    public function logout(): never
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/', $authHeader, $matches)) {
            $this->authService->logout($matches[1]);
        }
        Response::json([
            'message' => 'Logged out'
        ]);
    }
}