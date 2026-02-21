<?php

namespace Api\Common\Middleware;

use Api\Auth\Auth;
use Api\Auth\AuthUser;
use Api\Auth\Services\AuthService;
use Sonata\Framework\Attributes\NoAuth;
use Sonata\Framework\Http\Response;
use Sonata\Framework\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function handle(array $context, callable $next): mixed
    {
        $controller = $context['controller'] ?? null;
        $action = $context['action'] ?? null;

        if ($controller && $action && $this->isNoAuth($controller, $action)) {
            $this->authenticateOptional();

            try {
                return $next($context);
            } finally {
                Auth::clear();
            }
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\\s+(.*)$/i', $authHeader, $matches)) {
            Response::error('Missing token', 401);
        }

        $payload = $this->authService->validateToken($matches[1]);
        if (!$payload) {
            Response::error('Invalid token', 401);
        }

        Auth::set(new AuthUser(
            uuid: (string)$payload->sub,
            email: isset($payload->email) ? (string)$payload->email : null
        ));

        try {
            return $next($context);
        } finally {
            Auth::clear();
        }
    }

    private function authenticateOptional(): void
    {
        Auth::clear();

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\\s+(.*)$/i', $authHeader, $matches)) {
            return;
        }

        $payload = $this->authService->validateToken($matches[1]);
        if (!$payload || !isset($payload->sub)) {
            return;
        }

        Auth::set(new AuthUser(
            uuid: (string)$payload->sub,
            email: isset($payload->email) ? (string)$payload->email : null
        ));
    }

    private function isNoAuth(string $controller, string $action): bool
    {
        try {
            $reflection = new \ReflectionClass($controller);
            if (!empty($reflection->getAttributes(NoAuth::class))) {
                return true;
            }

            if ($reflection->hasMethod($action)) {
                $method = $reflection->getMethod($action);
                return !empty($method->getAttributes(NoAuth::class));
            }
        } catch (\ReflectionException) {
            return false;
        }

        return false;
    }
}
