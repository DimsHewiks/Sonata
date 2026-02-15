<?php

namespace Api\Auth\Controllers;

use Api\Auth\Auth;
use Api\Auth\DTOs\Request\LoginDTO;
use Api\Auth\DTOs\Request\RegisterDTO;
use Api\Auth\Services\AuthService;
use Api\User\Dto\Request\ProfileDescriptionUpdateDto;
use Api\User\Dto\Response\UserAvatarDto;
use Api\User\Dto\Response\UserFullDto;
use Sonata\Framework\MediaHelper\MediaHelper;
use Sonata\Framework\Attributes\Controller;
use Sonata\Framework\Attributes\From;
use Sonata\Framework\Attributes\Inject;
use Sonata\Framework\Attributes\NoAuth;
use Sonata\Framework\Attributes\Response as ResponseAttr;
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
    public function login(#[From('json')] LoginDTO $dto): never
    {
        try {
            $identifier = $dto->login ?: $dto->email;
            if (!$identifier) {
                Response::error('Имя учетной записи или почта уже используется', 400);
            }

            $result = $this->authService->login($identifier, $dto->password);
            if (!$result) {
                Response::error('Учетная запись не найдена', 404);
            }

            Response::json($result, 200);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error(
                'Ошибка входа',
                500,
                $e->getMessage()
            );
        }
    }

    #[Route(path: '/me', method: 'GET', summary: 'Профиль', description: 'Получение информации об авторизированном пользователе')]
    #[ResponseAttr(UserFullDto::class)]
    public function profile(): never
    {
        $profile = $this->authService->getProfile(Auth::getOrThrow()->uuid);
        if (!$profile) {
            Response::error('User not found', 404);
        }

        Response::json($profile, 200);
    }

    #[Route(path: '/me/description', method: 'PUT', summary: 'Описание профиля', description: 'Обновление описания профиля')]
    public function updateProfileDescription(#[From('json')] ProfileDescriptionUpdateDto $dto): never
    {
        try {
            $this->authService->updateProfileDescription(Auth::getOrThrow()->uuid, $dto->description);
            Response::json([
                'message' => 'Описание обновлено'
            ]);
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Unauthorized' ? 401 : 500;
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to update profile description', 500, $e->getMessage());
        }
    }

    #[Route(path: '/me/avatar', method: 'POST', summary: 'Обновление аватара')]
    #[ResponseAttr(UserAvatarDto::class)]
    public function updateAvatar(): UserAvatarDto
    {
        try {
            $media = new MediaHelper('/avatars');
            if (!$media->existFile('avatar')) {
                Response::error('Avatar file is required', 400);
            }

            $media->setNames(['avatar']);
            $uploadResult = $media->import();
            $avatarData = $uploadResult['avatar'] ?? null;
            if (!$avatarData) {
                Response::error('Avatar upload failed', 400);
            }

            $relativePath = $this->authService->updateAvatar(Auth::getOrThrow()->uuid, $avatarData);
            if (!$relativePath) {
                Response::error('Failed to save avatar', 500);
            }

            $dto = new UserAvatarDto();
            $dto->relativePath = (string)$relativePath;
            $dto->extension = strtolower((string)($avatarData['extension'] ?? ''));
            return $dto;
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Unauthorized' ? 401 : 500;
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to update avatar', 500, $e->getMessage());
        }
    }

    #[Route(path: '/registration', method: 'POST', summary: 'Регистрация', description: 'Метод регистрации нового юзера')]
    #[NoAuth]
    public function createAccount(#[From('formData')] RegisterDTO $dto): never
    {
        try {
            $avatarData = null;
            $media = new MediaHelper('/avatars');
            if ($media->existFile('avatar')) {
                $media->setNames(['avatar']);
                $uploadResult = $media->import();
                $avatarData = $uploadResult['avatar'] ?? null;
            }

            $this->authService->register(
                $dto->name ?? '',
                $dto->age ?? 0,
                $dto->login ?? '',
                $dto->email,
                $dto->password ?? '',
                $avatarData
            );
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
            $result = $this->authService->refreshFromRequest();
            if (!$result) {
                Response::error(
                    'Invalid or expired refresh token',
                    401
                );
            }

            Response::json($result);

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
        $this->authService->clearRefreshCookie();
        Response::json([
            'message' => 'Logged out'
        ]);
    }
}
