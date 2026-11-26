<?php
namespace App;

final class Router {
    public function __construct(
        private array $routes,
        private ?\Closure $controllerFactory = null
    ) {}
    public function dispatch(string $method, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($method === 'HEAD' && in_array($path, ['/', '/sitemap.xml', '/openapi.json', '/manifest.json', '/admin/manifest.json'], true)) $method = 'GET';
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            $arguments = self::matchPath($route['path'], $path);
            if ($arguments === null) continue;
            [$class, $action] = explode('@', $route['controller']);
            $fqcn = 'App\\Controllers\\' . $class;
            $factory = $this->controllerFactory ?? fn(string $c) => new $c();
            $controller = $factory($fqcn);
            $controller->{$action}(...$arguments);
            return;
        }
        (new Controllers\PublicController())->notFound();
    }

    /** Return ordered route arguments, or null when the literal path does not match. */
    public static function matchPath(string $template, string $path): ?array {
        if (!str_contains($template, '{')) return $template === $path ? [] : null;
        $parts = preg_split('/\{[^}]+\}/', $template);
        $literals = array_map(static fn(string $part): string => preg_quote($part, '#'), $parts);
        $pattern = '#^' . implode('([^/]+)', $literals) . '$#D';
        if (!preg_match($pattern, $path, $matches)) return null;
        return array_slice($matches, 1);
    }
}
