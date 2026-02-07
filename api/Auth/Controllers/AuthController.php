<?php

namespace Api\Auth\Controllers;

use Api\Auth\DTOs\Request\RegistDTO;
use Api\Auth\Services\AuthService;
use Sonata\Framework\Attributes\Controller;
use Sonata\Framework\Attributes\From;
use Sonata\Framework\Attributes\Inject;
use Sonata\Framework\Attributes\NoAuth;
use Sonata\Framework\Attributes\Route;
use Sonata\Framework\Attributes\Tag;
use Sonata\Framework\Http\Response;

#[Controller(prefix: '/api')]
#[Tag('Регистрация/Авторизация')]
class AuthController
{
    public function __construct(
        #[Inject] private AuthService $authService
    ) {}

    #[Route(path: '/login', method: 'POST', summary: 'Вход', description: 'Метод входа в систему')]
    #[NoAuth]
    public function login(#[From('json')] RegistDTO $dto): never
    {
        try {
            $tokens = $this->authService->login($dto->email, $dto->password);
            if (!$tokens) {
                Response::error('Invalid credentials', 401);
            }

            $this->setAccessHeader($tokens['access_token']);
            $this->setRefreshCookie($tokens['refresh_token']);

            Response::json([
                'token_type' => $tokens['token_type'],
                'expires_in' => $tokens['expires_in']
            ], 200);

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
            'user_uuid' => $payload->sub,
            'email' => $payload->email,
            'message' => 'Authenticated!'
        ], 200);
    }

    #[Route(path: '/registration', method: 'POST', summary: 'Регистрация', description: 'Метод регистрации нового юзера')]
    #[NoAuth]
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
    #[NoAuth]
    public function refresh(): never
    {
        try {
            $refreshToken = $this->getRefreshTokenFromRequest();
            if (!$refreshToken) {
                Response::error('Missing refresh token', 401);
            }

            $tokens = $this->authService->refresh(
                $refreshToken
            );

            if (!$tokens) {
                Response::error(
                    'Invalid or expired refresh token',
                    401
                );
            }

            $this->setAccessHeader($tokens['access_token']);
            $this->setRefreshCookie($tokens['refresh_token']);

            Response::json([
                'token_type' => $tokens['token_type'],
                'expires_in' => $tokens['expires_in']
            ]);

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
        $this->clearRefreshCookie();
        Response::json([
            'message' => 'Logged out'
        ]);
    }

    private function getRefreshTokenFromRequest(): ?string
    {
        $cookieName = $_ENV['REFRESH_COOKIE_NAME'] ?? 'refresh_token';
        if (!empty($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        return $input['refresh_token'] ?? null;
    }

    private function setAccessHeader(string $accessToken): void
    {
        header('Authorization: Bearer ' . $accessToken);
    }

    private function setRefreshCookie(string $refreshToken): void
    {
        $cookieName = $_ENV['REFRESH_COOKIE_NAME'] ?? 'refresh_token';
        $ttlDays = (int)($_ENV['REFRESH_COOKIE_TTL_DAYS'] ?? 30);
        $secure = !empty($_ENV['REFRESH_COOKIE_SECURE']) && ($_ENV['REFRESH_COOKIE_SECURE'] === '1');
        $sameSite = $_ENV['REFRESH_COOKIE_SAMESITE'] ?? 'Lax';

        setcookie($cookieName, $refreshToken, [
            'expires' => time() + ($ttlDays * 24 * 3600),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite
        ]);
    }

    private function clearRefreshCookie(): void
    {
        $cookieName = $_ENV['REFRESH_COOKIE_NAME'] ?? 'refresh_token';
        $secure = !empty($_ENV['REFRESH_COOKIE_SECURE']) && ($_ENV['REFRESH_COOKIE_SECURE'] === '1');
        $sameSite = $_ENV['REFRESH_COOKIE_SAMESITE'] ?? 'Lax';

        setcookie($cookieName, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite
        ]);
    }
}
