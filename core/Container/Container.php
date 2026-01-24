<?php

namespace Core\Container;

use Closure;
use ReflectionClass;
use ReflectionNamedType;

class Container implements ContainerInterface
{
    private array $definitions = [];
    private array $instances = [];

    public function set(string $id, callable|object|string|null $concrete = null): void
    {
        $this->definitions[$id] = $concrete ?? $id;
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }


    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $concrete = $this->definitions[$id] ?? $id;

        if (!is_string($concrete) || !class_exists($concrete)) {
            throw new \Exception("Service or class not found: $id");
        }

        // Singleton: кэшируем только зарегистрированные сервисы
        $shouldCache = isset($this->definitions[$id]);

        $reflection = new ReflectionClass($concrete);
        if (!$reflection->isInstantiable()) {
            throw new \Exception("Cannot instantiate $concrete");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            $instance = new $concrete();
        } else {
            $dependencies = [];
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                if (!$type || !$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    if ($param->isDefaultValueAvailable()) {
                        $dependencies[] = $param->getDefaultValue();
                    } else {
                        throw new \Exception("Cannot resolve parameter \${$param->getName()} in $concrete");
                    }
                } else {
                    $typeName = $type->getName();
                    $dependencies[] = $this->get($typeName);
                }
            }
            $instance = $reflection->newInstanceArgs($dependencies);
        }

        if ($shouldCache) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }
}