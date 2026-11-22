<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Closure;
use Kongono\Core\Graph\RouteNode;
use Kongono\Http\HttpException;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionAttribute;
use ReflectionClass;

/**
 * Fluent + PHP 8.5 Attribute HTTP router with groups, regex parameter constraints,
 * deterministic specificity, and proper 405 Method Not Allowed detection.
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    /** @var array<string, array<string, Route>> Static path -> HTTP method -> Route (O(1) lookup) */
    private array $staticRoutes = [];

    /** @var list<array{route: Route, regex: string}> Pre-compiled dynamic parameter routes */
    private array $dynamicRoutes = [];

    private string $groupPrefix = '';

    /** @var list<MiddlewareInterface> */
    private array $groupMiddleware = [];

    /** @param Closure|array{0: class-string, 1: string} $handler */
    public function get(string $path, Closure|array $handler): Route
    {
        return $this->add(['GET', 'HEAD'], $path, $handler);
    }

    /** @param Closure|array{0: class-string, 1: string} $handler */
    public function post(string $path, Closure|array $handler): Route
    {
        return $this->add(['POST'], $path, $handler);
    }

    /** @param Closure|array{0: class-string, 1: string} $handler */
    public function put(string $path, Closure|array $handler): Route
    {
        return $this->add(['PUT'], $path, $handler);
    }

    /** @param Closure|array{0: class-string, 1: string} $handler */
    public function patch(string $path, Closure|array $handler): Route
    {
        return $this->add(['PATCH'], $path, $handler);
    }

    /** @param Closure|array{0: class-string, 1: string} $handler */
    public function delete(string $path, Closure|array $handler): Route
    {
        return $this->add(['DELETE'], $path, $handler);
    }

    /**
     * @param list<string>                               $methods
     * @param Closure|array{0: class-string, 1: string}  $handler
     */
    public function add(array $methods, string $path, Closure|array $handler): Route
    {
        $normalizedMethods = array_values(array_unique(array_map('strtoupper', $methods)));
        $fullPath = $this->normalizePath($this->groupPrefix . '/' . ltrim($path, '/'));
        $route = new Route($normalizedMethods, $fullPath, $handler);
        if ([] !== $this->groupMiddleware) {
            $route->middleware(...$this->groupMiddleware);
        }
        $this->routes[] = $route;
        $this->indexRoute($route);

        return $route;
    }

    /**
     * Group routes under a shared path prefix and optional middleware.
     *
     * @param Closure(self): void      $callback
     * @param list<MiddlewareInterface> $middleware
     */
    public function group(string $prefix, Closure $callback, array $middleware = []): self
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $this->normalizePath($previousPrefix . '/' . ltrim($prefix, '/'));
        if ('/' === $this->groupPrefix) {
            $this->groupPrefix = '';
        }
        $this->groupMiddleware = [...$previousMiddleware, ...$middleware];

        try {
            $callback($this);
        } finally {
            $this->groupPrefix = $previousPrefix;
            $this->groupMiddleware = $previousMiddleware;
        }

        return $this;
    }

    /**
     * Register all methods decorated with #[Get], #[Post], #[Put], #[Patch], #[Delete] on a controller class.
     * Supports class-level #[Prefix] plus class- and method-level MiddlewareAttributeInterface attributes
     * such as #[Middleware(...)] and #[Can(...)].
     *
     * @param class-string $controllerClass
     */
    public function controller(string $controllerClass): self
    {
        $reflection = new ReflectionClass($controllerClass);

        $classPrefix = '';
        $prefixAttrs = $reflection->getAttributes(Prefix::class);
        if ([] !== $prefixAttrs) {
            $classPrefix = rtrim($prefixAttrs[0]->newInstance()->path, '/');
        }

        $classMiddlewareAttrs = array_map(
            static fn(ReflectionAttribute $attr): MiddlewareAttributeInterface => $attr->newInstance(),
            $reflection->getAttributes(MiddlewareAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF),
        );

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            if ([] === $attributes) {
                continue;
            }

            $methodMiddlewareAttrs = array_map(
                static fn(ReflectionAttribute $attr): MiddlewareAttributeInterface => $attr->newInstance(),
                $method->getAttributes(MiddlewareAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF),
            );
            $allMiddlewareAttrs = [...$classMiddlewareAttrs, ...$methodMiddlewareAttrs];

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $fullPath = '' !== $classPrefix
                    ? $classPrefix . '/' . ltrim($instance->path, '/')
                    : $instance->path;
                $route = $this->add($instance->methods, $fullPath, [$controllerClass, $method->getName()]);
                if (null !== $instance->name) {
                    $route->name($instance->name);
                }
                if ([] !== $allMiddlewareAttrs) {
                    $route->middlewareAttributes(...$allMiddlewareAttrs);
                }
            }
        }

        return $this;
    }

    /** Match a method and path using O(1) static map + pre-compiled regexes, throwing 404 / 405 HttpException. */
    public function match(string $method, string $path): RouteMatch
    {
        $upperMethod = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);
        $allowedMethods = [];

        if (isset($this->staticRoutes[$normalizedPath])) {
            $staticForPath = $this->staticRoutes[$normalizedPath];
            if (isset($staticForPath[$upperMethod])) {
                return new RouteMatch($staticForPath[$upperMethod], []);
            }
            foreach (array_keys($staticForPath) as $allowed) {
                $allowedMethods[] = $allowed;
            }
        }

        foreach ($this->dynamicRoutes as $entry) {
            if (1 !== preg_match($entry['regex'], $normalizedPath, $matches)) {
                continue;
            }
            $route = $entry['route'];
            if (in_array($upperMethod, $route->methods, true)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                return new RouteMatch($route, $params);
            }
            array_push($allowedMethods, ...$route->methods);
        }

        if ([] !== $allowedMethods) {
            $unique = array_values(array_unique($allowedMethods));
            sort($unique, SORT_STRING);
            throw new HttpException(
                statusCode: 405,
                message: sprintf('Method %s not allowed for %s.', $upperMethod, $normalizedPath),
                headers: ['Allow' => implode(', ', $unique)],
            );
        }

        throw new HttpException(404, sprintf('No route matches %s %s.', $upperMethod, $normalizedPath));
    }

    /**
     * Export an OPcache-friendly, scalar-only AOT compiled route table for controller routes.
     *
     * @return array{
     *     version: int,
     *     static: array<string, array<string, array{handler: array{0: class-string, 1: string}, name: ?string}>>,
     *     dynamic: list<array{methods: list<string>, path: string, regex: string, handler: array{0: class-string, 1: string}, name: ?string}>,
     *     skipped_closures: int
     * }
     */
    public function exportCompiledTable(): array
    {
        $static = [];
        $dynamic = [];
        $skippedClosures = 0;

        foreach ($this->routes as $route) {
            if ($route->handler instanceof Closure) {
                ++$skippedClosures;
                continue;
            }
            if (!str_contains($route->path, '{')) {
                foreach ($route->methods as $method) {
                    $static[$route->path][$method] = [
                        'handler' => $route->handler,
                        'name' => $route->routeName(),
                    ];
                }
            } else {
                $dynamic[] = [
                    'methods' => $route->methods,
                    'path' => $route->path,
                    'regex' => $this->compilePathRegex($route->path),
                    'handler' => $route->handler,
                    'name' => $route->routeName(),
                ];
            }
        }

        return [
            'version' => 1,
            'static' => $static,
            'dynamic' => $dynamic,
            'skipped_closures' => $skippedClosures,
        ];
    }

    /**
     * Render the AOT compiled route table as an OPcache-cacheable PHP file.
     */
    public function exportCompiledPhp(): string
    {
        $exported = var_export($this->exportCompiledTable(), true);

        return "<?php\n\ndeclare(strict_types=1);\n\n// Auto-generated by `kongo optimize` — zero-reflection AOT route table.\nreturn " . $exported . ";\n";
    }

    /**
     * Hydrate routes directly from an AOT compiled table without invoking ReflectionClass on controllers.
     *
     * @param array{
     *     version?: int,
     *     static?: array<string, array<string, array{handler: array{0: class-string, 1: string}, name: ?string}>>,
     *     dynamic?: list<array{methods: list<string>, path: string, regex: string, handler: array{0: class-string, 1: string}, name: ?string}>
     * } $compiled
     */
    public function loadCompiledTable(array $compiled): self
    {
        /** @var array<string, array{methods: list<string>, path: string, handler: array{0: class-string, 1: string}, name: ?string}> $groupedStatic */
        $groupedStatic = [];
        foreach ($compiled['static'] ?? [] as $path => $methodsMap) {
            foreach ($methodsMap as $method => $entry) {
                $key = $path . '|' . $entry['handler'][0] . '::' . $entry['handler'][1] . '|' . ($entry['name'] ?? '');
                if (!isset($groupedStatic[$key])) {
                    $groupedStatic[$key] = [
                        'methods' => [$method],
                        'path' => $path,
                        'handler' => $entry['handler'],
                        'name' => $entry['name'],
                    ];
                } else {
                    $groupedStatic[$key]['methods'][] = $method;
                }
            }
        }

        foreach ($groupedStatic as $item) {
            $route = $this->add($item['methods'], $item['path'], $item['handler']);
            if (null !== $item['name']) {
                $route->name($item['name']);
            }
        }

        foreach ($compiled['dynamic'] ?? [] as $item) {
            $normalizedMethods = array_values(array_unique(array_map('strtoupper', $item['methods'])));
            $route = new Route($normalizedMethods, $item['path'], $item['handler']);
            if (null !== $item['name']) {
                $route->name($item['name']);
            }
            $this->routes[] = $route;
            $this->dynamicRoutes[] = ['route' => $route, 'regex' => $item['regex']];
        }

        return $this;
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes;
    }

    /** @return list<RouteNode> */
    public function routeNodes(): array
    {
        return array_map(
            static fn(Route $r): RouteNode => new RouteNode($r->methods, $r->path, $r->handlerLabel(), $r->routeName()),
            $this->routes,
        );
    }

    /**
     * Generate a URL path from a named route, substituting parameters and optional query string.
     *
     * @param array<string, string|int> $params
     * @param array<string, string|int> $query
     */
    public function url(string $name, array $params = [], array $query = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->routeName() !== $name) {
                continue;
            }
            $built = preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::[^}]+)?\}/',
                static function (array $m) use ($name, $params): string {
                    $key = $m[1];
                    if (!array_key_exists($key, $params)) {
                        throw new HttpException(500, sprintf('Missing required route parameter "%s" for route "%s".', $key, $name));
                    }

                    return rawurlencode((string) $params[$key]);
                },
                $route->path,
            );
            $url = is_string($built) ? $built : $route->path;
            if ([] !== $query) {
                $url .= '?' . http_build_query($query);
            }

            return $url;
        }

        throw new HttpException(500, sprintf('Named route "%s" does not exist.', $name));
    }

    private function normalizePath(string $path): string
    {
        $clean = '/' . trim($path, '/');
        $collapsed = preg_replace('#/+#', '/', $clean);

        return is_string($collapsed) ? $collapsed : '/';
    }

    private function indexRoute(Route $route): void
    {
        if (!str_contains($route->path, '{')) {
            foreach ($route->methods as $method) {
                $this->staticRoutes[$route->path][$method] ??= $route;
            }

            return;
        }

        $this->dynamicRoutes[] = [
            'route' => $route,
            'regex' => $this->compilePathRegex($route->path),
        ];
    }

    private function compilePathRegex(string $template): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $m): string {
                $constraint = $m[2] ?? '[^/]+';

                return sprintf('(?P<%s>%s)', $m[1], $constraint);
            },
            $template,
        );

        return '#\A' . (is_string($regex) ? $regex : preg_quote($template, '#')) . '\z#';
    }
}
