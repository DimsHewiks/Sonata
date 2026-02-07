<?php
namespace Command\Cache;

use Sonata\Framework\Attributes\Controller;
use Sonata\Framework\Attributes\Route;
use Sonata\Framework\Cache\RoutesCache;

#[Controller(prefix: '/command/cache')]
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