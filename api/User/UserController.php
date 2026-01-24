<?php

namespace Api\User;

use Api\User\Dto\Response\UserResponse;
use Core\Attributes\Controller;
use Core\Attributes\Response;
use Core\Attributes\Route;
use Core\Attributes\Tag;

#[Controller(prefix: '/api')]
#[Tag('Пользователи', 'Работа с юзерами')]
class UserController
{
    #[Route(path: '/users', method: 'GET', summary: 'Регистрация пользователя', description: 'Метод, позволяющий регать юзера')]
    #[Response(UserResponse::class, isArray: true)]
    public function listUsers(): array
    {
        return [
            new UserResponse(id: 1, name: "Alex", email: ''),
            new UserResponse(id: 2, name: "Bob", email: ''),
        ];
    }


}