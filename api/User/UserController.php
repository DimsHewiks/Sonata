<?php
namespace Api\User;

use Core\Attributes\Controller;
use Core\Attributes\Route;
use Core\Database\Db;

#[Controller(prefix: '/api/user')]
class UserController
{
    #[Route(path: '/test', method: 'GET')]
    public function test()
    {
        return $_GET;
    }

    #[Route(path: '/hui', method: 'GET')]
    public function hui(): array
    {
        try{
            $stmt = Db::getInstance();
            $pdo = $stmt->prepare(/**@lang MariaDB*/"
            select * from users
        ");
            $pdo->execute();
            return $pdo->fetchAll();
        }
        catch (\PDOException $e) {
            return [
                "file"=> $e->getFile(),
                "line" => $e->getLine(),
                "text" => $e->getMessage(),
            ];
        }

    }
}