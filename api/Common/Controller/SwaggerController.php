<?php

namespace Api\Common\Controller;

use Core\Attributes\Controller;
use Core\Attributes\Route;
use Core\Attributes\Tag;
use Core\Cache\OpenApiCache;
use Core\OpenApi\OpenApiGenerator;

#[Controller(prefix: '')]
#[Tag('Swagger (Документация)', 'Методы работы над документацией')]
class SwaggerController
{
    #[Route(
        path: '/openapi.json', method: 'GET',
        summary: 'Получение документации',
        description: 'Метод, позволяющий получить документацию для отображения'
    )]
    public function openapiSpec(): array
    {
        $debug = getenv('APP_ENV') === 'dev';

        if (!$debug) {
            $cache = new OpenApiCache();
            $spec = $cache->get();
            if ($spec) {
                return $spec;
            }
        }

        $generator = new OpenApiGenerator();
        $spec = $generator->generate();

        if (!$debug) {
            $cache = $cache ?? new OpenApiCache();
            $cache->store($spec);
        }

        return $spec;
    }
}