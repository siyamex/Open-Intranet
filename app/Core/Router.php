<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\CsrfMiddleware;

final class Router
{
    private static ?self $instance = null;

    /** @var array<int, array{method: string, path: string, regex: string, handler: mixed, middleware: string[], name: ?string}> */
    private array $routes = [];

    /** @var array<string, string> */
    private array $named = [];

    /** @var array<int, array{prefix?: string, middleware?: string[]}> */
    private array $groupStack = [];

    /** @var string[] regexes of paths exempt from CSRF verification (e.g. token-authenticated APIs) */
    private array $csrfExempt = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function get(string $path, mixed $handler, ?string $name = null): void
    {
        $this->add('GET', $path, $handler, $name);
    }

    public function post(string $path, mixed $handler, ?string $name = null): void
    {
        $this->add('POST', $path, $handler, $name);
    }

    public function put(string $path, mixed $handler, ?string $name = null): void
    {
        $this->add('PUT', $path, $handler, $name);
    }

    public function delete(string $path, mixed $handler, ?string $name = null): void
    {
        $this->add('DELETE', $path, $handler, $name);
    }

    public function add(string $method, string $path, mixed $handler, ?string $name = null): void
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'] ?? '', '/');
            $middleware = array_merge($middleware, $group['middleware'] ?? []);
        }
        $full = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        $full = preg_replace('#/{2,}#', '/', $full) ?? $full;
        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $full) . '$#';
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $full,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => $middleware,
            'name' => $name,
        ];
        if ($name !== null) {
            $this->named[$name] = $full;
        }
    }

    /**
     * @param array{prefix?: string, middleware?: string[]} $attrs
     */
    public function group(array $attrs, callable $routes): void
    {
        $this->groupStack[] = $attrs;
        $routes($this);
        array_pop($this->groupStack);
    }

    public function csrfExempt(string $pathRegex): void
    {
        $this->csrfExempt[] = $pathRegex;
    }

    public function hasRoute(string $name): bool
    {
        return isset($this->named[$name]);
    }

    /**
     * Build an absolute URL for a named route.
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->named[$name])) {
            throw new \RuntimeException("Unknown route name: {$name}");
        }
        $path = $this->named[$name];
        $query = [];
        foreach ($params as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (str_contains($path, $placeholder)) {
                $path = str_replace($placeholder, rawurlencode((string) $value), $path);
            } else {
                $query[$key] = $value;
            }
        }
        $url = base_url($path);
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }

    /**
     * The normalized route path of the current request (base prefix stripped).
     */
    public function currentPath(): string
    {
        return $this->normalize((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    }

    public function dispatch(string $method, string $uri): void
    {
        $httpMethod = strtoupper($method);
        $effective = $httpMethod;
        if ($httpMethod === 'POST') {
            $spoofed = strtoupper((string) ($_POST['_method'] ?? ''));
            if (in_array($spoofed, ['PUT', 'DELETE', 'PATCH'], true)) {
                $effective = $spoofed;
            }
        }
        $path = $this->normalize($uri);

        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $effective) {
                $allowed[] = $route['method'];
                continue;
            }
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = rawurldecode($value);
                }
            }
            if (in_array($httpMethod, ['POST', 'PUT', 'DELETE', 'PATCH'], true) && !$this->isCsrfExempt($path)) {
                (new CsrfMiddleware())->handle();
            }
            foreach (array_values(array_unique($route['middleware'])) as $middlewareClass) {
                (new $middlewareClass())->handle();
            }
            $this->call($route['handler'], $params);
            return;
        }

        if ($allowed !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_unique($allowed)));
            View::render('errors/405', ['allowed' => array_unique($allowed)], null);
            return;
        }
        http_response_code(404);
        View::render('errors/404', [], null);
    }

    private function isCsrfExempt(string $path): bool
    {
        foreach ($this->csrfExempt as $regex) {
            if (preg_match($regex, $path)) {
                return true;
            }
        }
        return false;
    }

    private function call(mixed $handler, array $params): void
    {
        if (is_array($handler) && is_string($handler[0])) {
            $handler = [new $handler[0](), $handler[1]];
        }
        if (!is_callable($handler)) {
            throw new \RuntimeException('Route handler is not callable.');
        }
        $result = call_user_func_array($handler, array_values($params));
        if (is_string($result)) {
            echo $result;
        }
    }

    /**
     * Strip the app base path (from APP_URL) and /public prefix so routes
     * match whether the app is reached via /intra/..., /intra/public/...,
     * or a proper public/ document root.
     */
    private function normalize(string $uri): string
    {
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');
        $base = rtrim((string) parse_url((string) Config::env('APP_URL', ''), PHP_URL_PATH), '/');
        foreach ([$base . '/public/index.php', $base . '/public', $base, '/index.php'] as $candidate) {
            if ($candidate !== '' && $candidate !== '/' && str_starts_with($path, $candidate)) {
                $path = substr($path, strlen($candidate));
                break;
            }
        }
        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        return $path === '' ? '/' : $path;
    }
}
