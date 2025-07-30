<?php
namespace Command\Cache;

use Core\Attributes\Controller;
use Core\Attributes\Route;
use Core\Cache\RoutesCache;

#[Controller(prefix: '/command')]
class CacheController
{
    #[Route(path: '/clear', method: 'GET')]
    public function test():array
    {
        (new RoutesCache())->clear();
        return [
            "result" => "OK",
            "msg" => "Успешная очистка кеша роутинга"
        ];
    }
}