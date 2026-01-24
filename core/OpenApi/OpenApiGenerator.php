<?php

namespace Core\OpenApi;

use Core\Attributes\Controller as ControllerAttr;
use Core\Attributes\Route as RouteAttr;
use Core\Attributes\From;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class OpenApiGenerator
{
    private array $routes = [];

    private array $schemas = [];

    public function generate(): array
    {
        $this->scanControllers();
        return $this->buildSpec();
    }

    private function scanControllers(): void
    {
        $directories = [
            __DIR__ . '/../../api',
            __DIR__ . '/../../view',
            __DIR__ . '/../../commands'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') continue;

                $relative = str_replace($dir . '/', '', $file->getPathname());
                $className = ucfirst(basename($dir)) . '\\' . str_replace('/', '\\', substr($relative, 0, -4));

                if (!class_exists($className)) continue;

                $this->processController($className);
            }
        }
    }

    private function processController(string $className): void
    {
        $reflection = new ReflectionClass($className);
        $controllerAttrs = $reflection->getAttributes(ControllerAttr::class);

        if (empty($controllerAttrs)) return;

        $controllerPrefix = $controllerAttrs[0]->newInstance()->prefix;

        $tagAttr = null;
        foreach ($reflection->getAttributes(\Core\Attributes\Tag::class) as $attr) {
            $tagAttr = $attr->newInstance();
            break;
        }

        if ($tagAttr) {
            $tagName = $tagAttr->name;
            $tagDescription = $tagAttr->description ?? "Операции над {$tagName}";
        } else {
            $tagName = 'Default';
            $tagDescription = 'Базовые операции';
        }

        foreach ($reflection->getMethods() as $method) {
            $routeAttrs = $method->getAttributes(RouteAttr::class);
            if (empty($routeAttrs)) continue;

            $route = $routeAttrs[0]->newInstance();
            $fullPath = rtrim($controllerPrefix, '/') . '/' . ltrim($route->path, '/');

            $summary = $route->summary ?? $method->getName();
            $description = $route->description ?? '';

            $this->routes[] = [
                'path' => '/' . ltrim($fullPath, '/'),
                'method' => strtolower($route->method),
                'methodReflection' => $method,
                'summary' => $summary,
                'description' => $description,
                'tagName' => $tagName,
                'tagDescription' => $tagDescription
            ];
        }
    }

    /**
     * @throws \ReflectionException
     */
    private function getResponseSchema(\ReflectionMethod $method): array
    {
        $responseAttrs = $method->getAttributes(\Core\Attributes\Response::class);

        if (!empty($responseAttrs)) {
            $responseAttr = $responseAttrs[0]->newInstance();

            if ($responseAttr->class && class_exists($responseAttr->class)) {
                $shortName = basename(str_replace('\\', '/', $responseAttr->class));
                $this->collectSchema($responseAttr->class);

                if ($responseAttr->isArray) {
                    return [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/' . $shortName]
                    ];
                } else {
                    return ['$ref' => '#/components/schemas/' . $shortName];
                }
            }
        }

        $returnType = $method->getReturnType();
        if (!$returnType) {
            return ['type' => 'object'];
        }

        if ($returnType->getName() === 'array') {
            return ['type' => 'array', 'items' => ['type' => 'object']];
        }

        if (!$returnType->isBuiltin()) {
            $className = $returnType->getName();
            if (class_exists($className)) {
                $shortName = basename(str_replace('\\', '/', $className));
                $this->collectSchema($className);
                return ['$ref' => '#/components/schemas/' . $shortName];
            }
        }

        return ['type' => 'object'];
    }

    /**
     * @throws \ReflectionException
     */
    private function collectSchema(string $className): void
    {
        $shortName = basename(str_replace('\\', '/', $className));
        if (isset($this->schemas[$shortName])) {
            return;
        }

        $reflection = new \ReflectionClass($className);
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();
            $propertyType = $property->getType();

            $schema = ['type' => 'string'];
            if ($propertyType) {
                $typeName = $propertyType->getName();
                if (in_array($typeName, ['int', 'integer'])) {
                    $schema['type'] = 'integer';
                } elseif (in_array($typeName, ['float', 'double'])) {
                    $schema['type'] = 'number';
                } elseif (in_array($typeName, ['bool', 'boolean'])) {
                    $schema['type'] = 'boolean';
                }
            }

            $oaAttrs = $property->getAttributes(\OpenApi\Attributes\Property::class);
            if (!empty($oaAttrs)) {
                $oaProp = $oaAttrs[0]->newInstance();
                if (isset($oaProp->example)) {
                    $schema['example'] = $oaProp->example;
                }
                if (isset($oaProp->description)) {
                    $schema['description'] = $oaProp->description;
                }
            }

            $properties[$propertyName] = $schema;
        }

        $this->schemas[$shortName] = [
            'type' => 'object',
            'properties' => $properties
        ];
    }

    private function buildSpec(): array
    {
        $paths = [];
        $tagDescriptions = [];

        foreach ($this->routes as $route) {
            $params = $this->extractParameters($route['methodReflection']);

            $operation = [
                'summary' => $route['summary'],
                'operationId' => $route['methodReflection']->getName(),
                'tags' => [$route['tagName']],
                'responses' => [
                    '200' => [
                        'description' => 'Успешный ответ',
                        'content' => [
                            'application/json' => [
                                'schema' => $this->getResponseSchema($route['methodReflection'])
                            ]
                        ]
                    ]
                ]
            ];

            if (!empty($route['description'])) {
                $operation['description'] = $route['description'];
            }

            if (!empty($params['query'])) {
                foreach ($params['query'] as $name => $schema) {
                    $operation['parameters'][] = [
                        'name' => $name,
                        'in' => 'query',
                        'required' => false,
                        'schema' => $schema
                    ];
                }
            }

            if (!empty($params['body'])) {
                $operation['requestBody'] = $params['body'];
            }

            $path = $route['path'];
            $method = $route['method'];

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }
            $paths[$path][$method] = $operation;

            $tagDescriptions[$route['tagName']] = $route['tagDescription'];
        }

        $tags = [];
        foreach ($tagDescriptions as $name => $description) {
            $tags[] = [
                'name' => $name,
                'description' => $description
            ];
        }

        $result = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Sonata API',
                'version' => '1.0.0',
                'description' => 'Автоматически сгенерированная документация'
            ],
            'servers' => [
                [
                    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
                    'description' => 'Текущий сервер'
                ]
            ],
            'tags' => $tags,
            'paths' => $paths
        ];

        if (!empty($this->schemas)) {
            $result['components'] = ['schemas' => $this->schemas];
        }

        return $result;

    }

    private function extractParameters(ReflectionMethod $method): array
    {
        $query = [];
        $body = null;

        foreach ($method->getParameters() as $param) {
            $fromAttr = null;
            foreach ($param->getAttributes(From::class) as $attr) {
                $fromAttr = $attr->newInstance();
                break;
            }

            if (!$fromAttr) continue;

            $type = $param->getType();
            if (!$type || !$type instanceof \ReflectionNamedType || $type->isBuiltin()) continue;

            $dtoClass = $type->getName();
            if (!class_exists($dtoClass)) continue;

            $dtoReflection = new ReflectionClass($dtoClass);
            $properties = [];

            foreach ($dtoReflection->getProperties() as $property) {
                $propertyName = $property->getName();
                $propertyType = $property->getType();

                $schema = ['type' => 'string'];
                if ($propertyType) {
                    $typeName = $propertyType->getName();
                    if (in_array($typeName, ['int', 'integer'])) {
                        $schema['type'] = 'integer';
                    } elseif (in_array($typeName, ['float', 'double'])) {
                        $schema['type'] = 'number';
                    } elseif (in_array($typeName, ['bool', 'boolean'])) {
                        $schema['type'] = 'boolean';
                    }
                }

                $oaAttrs = $property->getAttributes(\OpenApi\Attributes\Property::class);
                if (!empty($oaAttrs)) {
                    $oaProp = $oaAttrs[0]->newInstance();
                    if (isset($oaProp->example)) {
                        $schema['example'] = $oaProp->example;
                    }
                    if (isset($oaProp->description)) {
                        $schema['description'] = $oaProp->description;
                    }
                }

                $properties[$propertyName] = $schema;
            }

            if ($fromAttr->source === 'json') {
                $body = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => $properties
                            ]
                        ]
                    ]
                ];
            } elseif ($fromAttr->source === 'query') {
                foreach ($properties as $name => $schema) {
                    $query[$name] = $schema;
                }
            }
        }

        return ['query' => $query, 'body' => $body];
    }
}