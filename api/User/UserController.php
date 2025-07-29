<?php
namespace Api\User;

use Core\Attributes\Controller;
use Core\Attributes\Route;

#[Controller(prefix: '/api/user')]
class UserController
{
    #[Route(path: '/test', method: 'GET')]
    public function test()
    {
        return ['status' => 'OK'];
    }

    #[Route(path: '/hui', method: 'GET')]
    public function hui()
    {
        return ['status' => 'hui'];
    }
}