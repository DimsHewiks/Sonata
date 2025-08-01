<?php
namespace Core;

use Core\Attributes\Params;
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
        $uri = strtok($uri, '?');

        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $uri, $method)) {
                try {
                    $controller = new $route['controller']();
                    $methodName = $route['action'];

                    $params = $this->resolveParameters($controller, $methodName);
                    $response = call_user_func_array(
                        [$controller, $methodName],
                        $params
                    );

                    $this->sendResponse($response);
                    return;

                } catch (\Throwable $e) {
                    $this->sendError(500, $e->getMessage());
                    return;
                }
            }
        }

        $this->sendNotFound($uri, $method);
    }

    public function registerRoutesFromAnnotations(string $controllerClass): void
    {
        try {
            $reflection = new \ReflectionClass($controllerClass);

            foreach ($reflection->getAttributes(\Core\Attributes\Route::class) as $classAttr) {
                $classRoute = $classAttr->newInstance();

                foreach ($reflection->getMethods() as $method) {
                    foreach ($method->getAttributes(\Core\Attributes\Route::class) as $methodAttr) {
                        $methodRoute = $methodAttr->newInstance();

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

            $controllerAttr = $reflection->getAttributes(\Core\Attributes\Controller::class);
            if (empty($controllerAttr)) return;

            $prefix = $controllerAttr[0]->newInstance()->prefix;

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

    /**
     * @throws \ReflectionException
     */
    private function resolveMethodParameters(object $controller, string $methodName, array $urlParams): array
    {
        $reflectionMethod = new \ReflectionMethod($controller, $methodName);
        $parameters = [];

        // Обрабатываем атрибут Params если есть
        foreach ($reflectionMethod->getAttributes(Params::class) as $attr) {
            $paramsAttr = $attr->newInstance();
            $dtoClass = $paramsAttr->class;

            $inputData = match($paramsAttr->from) {
                'query' => $_GET,
                'body' => json_decode(file_get_contents('php://input'), true) ?? [],
                default => array_merge($_REQUEST, $urlParams)
            };

            $parameters[] = new $dtoClass($inputData);
        }

        foreach ($reflectionMethod->getParameters() as $param) {
            if (isset($urlParams[$param->getName()])) {
                $parameters[] = $urlParams[$param->getName()];
            }
        }

        return $parameters;
    }

    private function sendResponse($response): void
    {
        if (is_array($response) || is_object($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            echo $response;
        }
    }

    private function sendNotFound(string $uri, string $method): void
    {
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

    private function sendError(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    }


    private function getRequestData(string $source): array
    {
        return match($source) {
            'query' => $_GET,
            'jsonBody' => $this->getJsonBody(),
            'formData' => $this->getFormData(),
            'request' => $_REQUEST,
            default => []
        };
    }
    private function getJsonBody(): array
    {
        $content = file_get_contents('php://input');
        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function getFormData(): array
    {
        // Объединяем POST данные и файлы
        return array_merge($_POST, $_FILES);
    }
    /**
     * @throws \ReflectionException
     */
    private function resolveParameters(object $controller, string $method): array
    {
        $reflection = new \ReflectionMethod($controller, $method);
        $parameters = [];
        // Обрабатываем атрибут Params
        foreach ($reflection->getAttributes(Params::class) as $attribute) {
            $paramsAttr = $attribute->newInstance();
            $data = $this->getRequestData($paramsAttr->from);
            $dto = new $paramsAttr->class($data);
            // Валидация
            if ($errors = $dto->validate()) {

                $this->sendError(400, implode(' | ',$errors));
                exit;
            }

            $parameters[] = $dto;
        }

        return $parameters;
    }
    private function matchRoute(array $route, string $uri, string $method): bool
    {
        if (strtoupper($route['method']) !== strtoupper($method)) {
            return false;
        }

        return (bool)preg_match($route['pattern'], $uri, $matches);
    }

}