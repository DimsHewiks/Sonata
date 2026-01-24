<?php

namespace Api\User;

use Api\User\Dto\Response\UserResponse;
use Api\User\Dto\UserCreateDto;
use Core\Attributes\Controller;
use Core\Attributes\Response;
use Core\Attributes\Route;
use Core\Attributes\From;
use Core\Attributes\Tag;
use OpenApi\Tests\Fixtures\Parser\User;

#[Controller(prefix: '/api')]
#[Tag('Пользователи', 'Регистрация пользователя')]
class UserController
{
    /**
     * @return UserResponse[]
     */
    /**
     * Получить список пользователей
     *
     * @return UserResponse[]
     */
    #[Route(path: '/users', method: 'GET')]
    #[Response(UserResponse::class, isArray: true)]
    public function listUsers(): array
    {
        return [
            new UserResponse(id: 1, name: "Alex", email: ''),
            new UserResponse(id: 2, name: "Bob", email: ''),
        ];
    }


}