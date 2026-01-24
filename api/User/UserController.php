<?php

namespace Api\User;

use Core\Attributes\Controller;
use Core\Attributes\Route;
use Core\Attributes\From;
use Api\User\Dto\UserParams;

#[Controller(prefix: '/api')]
class UserController
{
    #[Route(path: '/users', method: 'GET')]
    public function userById( #[From('query')] UserParams $params ): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => $params->id,
                'name' => $params->name,
                'email' => $params->email
            ]
        ];
    }

    #[Route(path: '/list/{id}', method: 'GET')]
    public function getUserById(int $id): array
    {
        return ['user_id' => $id];
    }


}