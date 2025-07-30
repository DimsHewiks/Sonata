<?php
namespace Core;

use Core\Cache\RoutesCache;

class Router
{
    private array $routes = [];
    private RoutesCache $cache;
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->cache = new RoutesCache();
        $this->debug = $debug;
    }

    public function addRoute(
        string $pattern,
        string $method,
        string $controller,
        string $action
    ): void {
        $this->routes[] = [
            'pattern' => '#^'.$pattern.'$#',
            'method' => $method,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }



    public function dispatch(string $uri, string $method): void
    {
        // Удаляем query-параметры
        $uri = strtok($uri, '?');

        error_log("Dispatching: $method $uri");

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            error_log("Checking route: " . $route['pattern']);

            if (preg_match($route['pattern'], $uri, $matches)) {
                error_log("Route matched with params: " . print_r($matches, true));

                // Фильтруем только именованные параметры
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                try {
                    $controller = new $route['controller']();
                    $response = call_user_func_array(
                        [$controller, $route['action']],
                        $params
                    );

                    if(is_array($response)){
                        header('Content-Type: application/json');
                        echo json_encode($response);
                        return;
                    }
                    echo $response;
                    return;
                } catch (\Throwable $e) {
                    error_log("Controller error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => 'Internal Server Error']);
                    return;
                }
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Route not found',
            'requested' => $uri,
            'method' => $method,
            'available_routes' => array_map(fn($r) => [
                'path' => $r['pattern'],
                'method' => $r['method']
            ], $this->routes)
        ]);
    }

    public function registerRoutesFromAnnotations(string $controllerClass): void
    {
        try {
            $reflection = new \ReflectionClass($controllerClass);

            // Получаем атрибуты класса
            foreach ($reflection->getAttributes(\Core\Attributes\Route::class) as $classAttr) {
                $classRoute = $classAttr->newInstance();

                // Обрабатываем методы класса
                foreach ($reflection->getMethods() as $method) {
                    foreach ($method->getAttributes(\Core\Attributes\Route::class) as $methodAttr) {
                        $methodRoute = $methodAttr->newInstance();

                        // Формируем полный путь
                        $fullPath = rtrim($classRoute->path, '/') . '/' . ltrim($methodRoute->path, '/');

                        $this->addRoute(
                            $fullPath,
                            $methodRoute->method,
                            $controllerClass,
                            $method->getName()
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Route registration error: " . $e->getMessage());
        }
    }

    public function registerControllers(): void
    {
        $controllersDirectory = [
            __DIR__.'/../api',
            __DIR__.'/../view',
            __DIR__.'/../commands'
        ];

        if (!$this->debug && $cachedRoutes = $this->cache->get()) {
            $this->routes = $cachedRoutes;
            return;
        }

        $finder = new ControllerFinder();
        foreach ($controllersDirectory as $directory) {
            foreach ($finder->find($directory) as $controller) {
                $this->registerController($controller);
            }
        }


        if (!$this->debug) {
            $this->cache->store($this->routes);
        }
    }
    private function registerController(string $className): void
    {
        try {
            $reflection = new \ReflectionClass($className);

            // Получаем префикс из аннотации Controller
            $controllerAttr = $reflection->getAttributes(\Core\Attributes\Controller::class);
            if (empty($controllerAttr)) return;

            $prefix = $controllerAttr[0]->newInstance()->prefix;

            // Регистрируем методы
            foreach ($reflection->getMethods() as $method) {
                $routeAttrs = $method->getAttributes(\Core\Attributes\Route::class);
                if (empty($routeAttrs)) continue;

                $route = $routeAttrs[0]->newInstance();
                $fullPath = rtrim($prefix, '/') . '/' . ltrim($route->path, '/');

                $this->addRoute(
                    $fullPath,
                    $route->method,
                    $className,
                    $method->getName()
                );
            }
        } catch (\Exception $e) {
            error_log("Controller registration error: " . $e->getMessage());
        }
    }


}