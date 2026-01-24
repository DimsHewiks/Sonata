<?php
namespace Core;

use Core\Attributes\From;
use Core\Attributes\Params;
use Core\Cache\RoutesCache;

class Router
{
    private array $routes = [];
    private RoutesCache $cache;
    private bool $debug;

    private \Core\Container\ContainerInterface $container;

    public function __construct(\Core\Container\ContainerInterface $container, bool $debug = false)
    {
        $this->container = $container;
        $this->cache = new RoutesCache();
        $this->debug = $debug;
    }

    public function addRoute(string $pattern, string $method, string $controller, string $action): void
    {
        $this->routes[] = [
            'pattern' => $this->convertRoutePattern($pattern),
            'method' => $method,
            'controller' => $controller,
            'action' => $action
        ];
    }
    private function convertRoutePattern(string $pattern): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '([^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }



    public function dispatch(string $uri, string $method): void
    {
        $uri = strtok($uri, '?');

        foreach ($this->routes as $route) {
            $matches = $this->matchRoute($route, $uri, $method);
            if ($matches !== null) {
                try {
                    $controller = $this->container->get($route['controller']);
                    $methodName = $route['action'];

                    // Передаём URL-параметры в resolveParameters
                    $params = $this->resolveParameters($controller, $methodName, $matches);
                    $response = call_user_func_array([$controller, $methodName], $params);

                    $this->sendResponse($response);
                    return;

                } catch (\Throwable $e) {
                    $this->sendError(500, $e->getMessage(), $e->getTraceAsString());
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

    private function sendResponse(mixed $data): void
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function sendNotFound(string $uri, string $method): void
    {
        $this->sendError(404, 'Route not found');
    }

    private function sendError(int $code, string $message, ?string $trace = null): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => ($this->debug && $trace) ? $trace : null
            ]
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
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
    private function resolveParameters(object $controller, string $method, array $urlMatches = []): array
    {
        $reflection = new \ReflectionMethod($controller, $method);
        $parameters = [];
        $urlIndex = 0;

        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();

            if ($type && $type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $fromAttr = null;
                foreach ($param->getAttributes(\Core\Attributes\From::class) as $attr) {
                    $fromAttr = $attr->newInstance();
                    break;
                }

                if (!$fromAttr) {
                    throw new \LogicException(
                        "Parameter \${$param->getName()} in {$reflection->getDeclaringClass()->getName()}::{$method}() " .
                        "must be annotated with #[From('source')]"
                    );
                }

                $data = match ($fromAttr->source) {
                    'query' => $_GET,
                    'json' => $this->getJsonBody(),
                    'formData' => $this->getFormData(),
                    default => throw new \InvalidArgumentException("Unsupported source: {$fromAttr->source}")
                };

                $dtoClass = $type->getName();
                $dto = new $dtoClass($data);

                if (method_exists($dto, 'validate')) {
                    $errors = $dto->validate();
                    if (!empty($errors)) {
                        $errorMessage = is_array($errors)
                            ? implode(' | ', array_map(
                                fn($k, $v) => "$k: $v",
                                array_keys($errors),
                                array_values($errors)
                            ))
                            : (string)$errors;

                        $this->sendError(400, $errorMessage);
                        exit;
                    }
                }

                $parameters[] = $dto;
            }
            else {
                if (isset($urlMatches[$urlIndex])) {
                    $parameters[] = $urlMatches[$urlIndex];
                    $urlIndex++;
                } elseif ($param->isDefaultValueAvailable()) {
                    $parameters[] = $param->getDefaultValue();
                } else {
                    throw new \LogicException("Missing URL parameter for \${$param->getName()}");
                }
            }
        }

        return $parameters;
    }
    private function matchRoute(array $route, string $uri, string $method): ?array
    {
        if (strtoupper($route['method']) !== strtoupper($method)) {
            return null;
        }

        $matches = [];
        if (preg_match($route['pattern'], $uri, $matches)) {
            array_shift($matches);
            return $matches;
        }

        return null;
    }

}