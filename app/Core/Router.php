<?php
/**
 * HTTP router with named parameters, middleware and method spoofing support.
 *
 * Routes are registered per HTTP verb. Handlers are [ControllerClass, method]
 * pairs resolved and invoked at dispatch time. Middleware are class names run
 * before the controller and may short-circuit the request.
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    /** @var array<string, array<int, array{pattern:string, handler:mixed, middleware:array<int,string>}>> */
    private array $routes = [
        'GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => [],
    ];

    /** @var array<int, string> */
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /**
     * Register a set of routes sharing a prefix and/or middleware.
     *
     * @param array{prefix?:string, middleware?:array<int,string>} $attributes
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . ($attributes['prefix'] ?? '');
        $this->groupMiddleware = array_merge($previousMiddleware, $attributes['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function add(string $method, string $path, mixed $handler, array $middleware): void
    {
        $full = $this->groupPrefix . $path;
        $full = '/' . trim($full, '/');
        if ($full === '/') {
            $full = '/';
        }
        $this->routes[$method][] = [
            'pattern'    => $this->compile($full),
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    private function compile(string $path): string
    {
        // Convert {param} to named capture groups.
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $middleware->handle($request);
                }

                $this->invoke($route['handler'], $request, $params);
                return;
            }
        }

        $this->notFound($request);
    }

    private function invoke(mixed $handler, Request $request, array $params): void
    {
        if (is_array($handler)) {
            [$class, $action] = $handler;
            if (!class_exists($class)) {
                throw new RuntimeException("Controller not found: {$class}");
            }
            $controller = new $class();
            $controller->$action($request, $params);
            return;
        }

        if (is_callable($handler)) {
            $handler($request, $params);
            return;
        }

        throw new RuntimeException('Invalid route handler.');
    }

    private function notFound(Request $request): void
    {
        if ($request->wantsJson()) {
            Response::json(['error' => 'Not found'], 404);
        }
        http_response_code(404);
        echo View::render('errors/404', ['title' => 'Pagina niet gevonden'], 'layouts/blank');
        exit;
    }
}
