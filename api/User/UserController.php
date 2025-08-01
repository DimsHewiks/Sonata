<?php
namespace Api\User;

use Api\User\Dto\TestDto;
use Api\User\Dto\UserParams;
use Core\Attributes\Controller;
use Core\Attributes\Params;
use Core\Attributes\Route;
use Core\Database\Db;

#[Controller(prefix: '/api/user')]
class UserController
{
    #[Route(path: '/test', method: 'GET')]
    #[Params(TestDto::class, 'query')]
    public function test(TestDto $params)
    {
        return $params;
    }

    #[Route(path: '/hui', method: 'GET')]
    public function hui(): array
    {
        return [];
    }
    #[Route(path: '/users', method: 'GET')]
    #[Params(
        UserParams::class,
        'query')
    ]
    public function userById(UserParams $params): array
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
}