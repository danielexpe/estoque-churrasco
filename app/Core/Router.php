<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        $path = rtrim($path, '/') ?: '/';

        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->compile($route['path']);

            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (mixed $value, string|int $key): bool => is_string($key),
                ARRAY_FILTER_USE_BOTH
            );

            $this->runMiddleware($route['middleware']);

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->{$action}(...array_values($params));
            } else {
                $handler(...array_values($params));
            }

            return;
        }

        Response::abort(404, 'errors/404');
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => rtrim($path, '/') ?: '/',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            '(?P<$1>[^/]+)',
            $path
        ) ?: $path;

        return '#^' . $pattern . '$#';
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $item) {
            if ($item === 'auth' && !Auth::check()) {
                Flash::error('Faça login para continuar.');
                Response::redirect('/login');
            }

            if ($item === 'guest' && Auth::check()) {
                Response::redirect('/');
            }

            if (str_starts_with($item, 'role:')) {
                if (!Auth::check()) {
                    Flash::error('Faça login para continuar.');
                    Response::redirect('/login');
                }

                $role = substr($item, 5);

                if (Auth::role() !== $role) {
                    Response::abort(403, 'errors/403');
                }
            }
        }
    }
}
