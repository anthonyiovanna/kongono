<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Closure;
use Kongono\Core\CoreException;
use Kongono\Core\Graph\RouteNode;
use Kongono\Http\HttpException;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Fluent + PHP 8.5 Attribute HTTP router with groups, regex parameter constraints,
 * deterministic specificity, and proper 405 Method Not Allowed detection.
 */
final class Router
{
    private const int DYNAMIC_CHUNK_SIZE = 16;

    /** @var list<Route> */
    private array $routes = [];

    /** @var array<string, array<string, Route>> Static path -> HTTP method -> Route (O(1) lookup) */
    private array $staticRoutes = [];

    /** @var list<array{route: Route, regex: string}> Pre-compiled dynamic parameter routes */
    private array $dynamicRoutes = [];

    /**
     * Method-indexed group-chunked PCRE2 (*MARK:idx) regexes for sub-linear dynamic route dispatch.
     *
     * @var ?array<string, list<array{regex: ?string, routes: array<int, array{route: Route, regex: string}>}>>
     */
    private ?array $dynamicMethodChunks = null;

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
        $handlerAttrs = $this->collectHandlerMiddlewareAttributes($handler);
        if ([] !== $handlerAttrs) {
            $route->middlewareAttributes(...$handlerAttrs);
        }
        $this->routes[] = $route;
        $this->indexRoute($route);

        return $route;
    }

    /**
     * Reflect class/method or closure MiddlewareAttributeInterface attributes so direct routes
     * enforce #[Authenticated], #[Can], #[VerifyWebhook], and #[Middleware] identically to ->controller().
     *
     * @param Closure|array{0: class-string, 1: string} $handler
     * @return list<MiddlewareAttributeInterface>
     */
    private function collectHandlerMiddlewareAttributes(Closure|array $handler): array
    {
        if ($handler instanceof Closure) {
            return array_map(
                static fn(ReflectionAttribute $attr): MiddlewareAttributeInterface => $attr->newInstance(),
                (new ReflectionFunction($handler))->getAttributes(MiddlewareAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF),
            );
        }

        [$controllerClass, $methodName] = $handler;
        if (!class_exists($controllerClass)) {
            return [];
        }
        $rc = new ReflectionClass($controllerClass);
        $attrs = array_map(
            static fn(ReflectionAttribute $attr): MiddlewareAttributeInterface => $attr->newInstance(),
            $rc->getAttributes(MiddlewareAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF),
        );
        if ($rc->hasMethod($methodName)) {
            $methodAttrs = array_map(
                static fn(ReflectionAttribute $attr): MiddlewareAttributeInterface => $attr->newInstance(),
                $rc->getMethod($methodName)->getAttributes(MiddlewareAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF),
            );
            $attrs = [...$attrs, ...$methodAttrs];
        }

        return $attrs;
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

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            if ([] === $attributes) {
                continue;
            }

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $fullPath = '' !== $classPrefix
                    ? $classPrefix . '/' . ltrim($instance->path, '/')
                    : $instance->path;
                $route = $this->add($instance->methods, $fullPath, [$controllerClass, $method->getName()]);
                if (null !== $instance->name) {
                    $route->name($instance->name);
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

        if ([] !== $this->dynamicRoutes) {
            $chunksByMethod = $this->dynamicMethodChunks ??= $this->buildDynamicMethodChunks();
            $methodChunks = $chunksByMethod[$upperMethod] ?? [];

            foreach ($methodChunks as $chunk) {
                $matchedEntry = null;
                if (null !== $chunk['regex']) {
                    if (1 === preg_match($chunk['regex'], $normalizedPath, $chunkMatches)) {
                        $mark = $chunkMatches['MARK'] ?? null;
                        if (is_string($mark) && isset($chunk['routes'][(int) $mark])) {
                            $matchedEntry = $chunk['routes'][(int) $mark];
                        }
                    }
                } else {
                    foreach ($chunk['routes'] as $candidate) {
                        if (1 === preg_match($candidate['regex'], $normalizedPath)) {
                            $matchedEntry = $candidate;
                            break;
                        }
                    }
                }

                if (null !== $matchedEntry && 1 === preg_match($matchedEntry['regex'], $normalizedPath, $matches)) {
                    $params = [];
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }

                    return new RouteMatch($matchedEntry['route'], $params);
                }
            }

            // No route matched for $upperMethod — scan remaining methods only to produce exact 405 Allow header
            foreach ($this->dynamicRoutes as $entry) {
                if (!in_array($upperMethod, $entry['route']->methods, true) && 1 === preg_match($entry['regex'], $normalizedPath)) {
                    array_push($allowedMethods, ...$entry['route']->methods);
                }
            }
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
     * Export an OPcache-friendly, scalar-only AOT compiled route table for controller routes,
     * preserving ALL class- and method-level MiddlewareAttributeInterface security gates (#[Can], #[Middleware], etc.)
     * and refusing to silently strip non-serializable runtime route/group middleware.
     *
     * @return array{
     *     version: int,
     *     static: array<string, array<string, array{
     *         handler: array{0: class-string, 1: string},
     *         name: ?string,
     *         middleware_attributes: list<array{class: class-string<MiddlewareAttributeInterface>, args: array<int|string, mixed>}>
     *     }>>,
     *     dynamic: list<array{
     *         methods: list<string>,
     *         path: string,
     *         regex: string,
     *         handler: array{0: class-string, 1: string},
     *         name: ?string,
     *         middleware_attributes: list<array{class: class-string<MiddlewareAttributeInterface>, args: array<int|string, mixed>}>
     *     }>,
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
            if ([] !== $route->routeMiddleware()) {
                throw new CoreException(sprintf(
                    'Cannot AOT-compile route "%s" (%s): route carries runtime middleware objects that cannot be serialized without dropping security gates. Use #[Middleware(...)] / MiddlewareAttributeInterface attributes or global middleware instead.',
                    $route->path,
                    $route->handlerLabel(),
                ));
            }

            $serializedAttrs = $this->exportControllerMiddlewareAttributes($route->handler);
            if (count($route->routeMiddlewareAttributes()) > count($serializedAttrs)) {
                throw new CoreException(sprintf(
                    'Cannot AOT-compile route "%s" (%s): route carries fluent middlewareAttributes() that are not declared on the controller class/method and would be dropped. Declare them as PHP attributes on the controller instead.',
                    $route->path,
                    $route->handlerLabel(),
                ));
            }

            if (!str_contains($route->path, '{')) {
                foreach ($route->methods as $method) {
                    $static[$route->path][$method] ??= [
                        'handler' => $route->handler,
                        'name' => $route->routeName(),
                        'middleware_attributes' => $serializedAttrs,
                    ];
                }
            } else {
                $dynamic[] = [
                    'methods' => $route->methods,
                    'path' => $route->path,
                    'regex' => $this->compilePathRegex($route->path),
                    'handler' => $route->handler,
                    'name' => $route->routeName(),
                    'middleware_attributes' => $serializedAttrs,
                ];
            }
        }

        return [
            'version' => 2,
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

        return "<?php\n\ndeclare(strict_types=1);\n\n// Auto-generated by `kongo optimize` — zero-reflection AOT route table (v2 with security attributes).\nreturn " . $exported . ";\n";
    }

    /**
     * Hydrate routes directly from an AOT compiled table (v2+) while restoring every security middleware attribute.
     * Fails closed on legacy v1 tables or entries missing `middleware_attributes`.
     *
     * @param array{
     *     version?: int,
     *     static?: array<string, array<string, array{
     *         handler: array{0: class-string, 1: string},
     *         name: ?string,
     *         middleware_attributes?: list<array{class: class-string<MiddlewareAttributeInterface>, args: array<int|string, mixed>}>
     *     }>>,
     *     dynamic?: list<array{
     *         methods: list<string>,
     *         path: string,
     *         regex: string,
     *         handler: array{0: class-string, 1: string},
     *         name: ?string,
     *         middleware_attributes?: list<array{class: class-string<MiddlewareAttributeInterface>, args: array<int|string, mixed>}>
     *     }>
     * } $compiled
     */
    public function loadCompiledTable(array $compiled): self
    {
        if (($compiled['version'] ?? 0) < 2) {
            throw new CoreException('Refusing to load legacy AOT route table (version < 2) that omits route middleware attributes. Re-run `kongo optimize`.');
        }

        /** @var array<string, array{methods: list<string>, path: string, handler: array{0: class-string, 1: string}, name: ?string, middleware_attributes: list<array{class: class-string<MiddlewareAttributeInterface>, args: array<int|string, mixed>}>}> $groupedStatic */
        $groupedStatic = [];
        foreach ($compiled['static'] ?? [] as $path => $methodsMap) {
            foreach ($methodsMap as $method => $entry) {
                if (!array_key_exists('middleware_attributes', $entry)) {
                    throw new CoreException(sprintf('Compiled static route "%s %s" is missing middleware_attributes metadata.', $method, $path));
                }
                $key = $path . '|' . $entry['handler'][0] . '::' . $entry['handler'][1] . '|' . ($entry['name'] ?? '');
                if (!isset($groupedStatic[$key])) {
                    $groupedStatic[$key] = [
                        'methods' => [$method],
                        'path' => $path,
                        'handler' => $entry['handler'],
                        'name' => $entry['name'],
                        'middleware_attributes' => $entry['middleware_attributes'],
                    ];
                } else {
                    $groupedStatic[$key]['methods'][] = $method;
                }
            }
        }

        foreach ($groupedStatic as $item) {
            $normalizedMethods = array_values(array_unique(array_map('strtoupper', $item['methods'])));
            $fullPath = $this->normalizePath($item['path']);
            $route = new Route($normalizedMethods, $fullPath, $item['handler']);
            if (null !== $item['name']) {
                $route->name($item['name']);
            }
            $attrs = $this->hydrateMiddlewareAttributes($item['middleware_attributes']);
            if ([] !== $attrs) {
                $route->middlewareAttributes(...$attrs);
            }
            $this->routes[] = $route;
            $this->indexRoute($route);
        }

        foreach ($compiled['dynamic'] ?? [] as $item) {
            if (!array_key_exists('middleware_attributes', $item)) {
                throw new CoreException(sprintf('Compiled dynamic route "%s" is missing middleware_attributes metadata.', $item['path']));
            }
            $normalizedMethods = array_values(array_unique(array_map('strtoupper', $item['methods'])));
            $route = new Route($normalizedMethods, $item['path'], $item['handler']);
            if (null !== $item['name']) {
                $route->name($item['name']);
            }
            $attrs = $this->hydrateMiddlewareAttributes($item['middleware_attributes']);
            if ([] !== $attrs) {
                $route->middlewareAttributes(...$attrs);
            }
            $this->routes[] = $route;
            $this->dynamicRoutes[] = ['route' => $route, 'regex' => $item['regex']];
        }
        $this->dynamicMethodChunks = null;

        return $this;
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @return list<array{class: class-string<MiddlewareAttributeInterface>, args: array<int|string, mixed>}>
     */
    private function exportControllerMiddlewareAttributes(array $handler): array
    {
        [$className, $methodName] = $handler;
        $refClass = new ReflectionClass($className);
        $refMethod = new ReflectionMethod($className, $methodName);

        $serialized = [];
        foreach ([
            ...$refClass->getAttributes(MiddlewareAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF),
            ...$refMethod->getAttributes(MiddlewareAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF),
        ] as $attr) {
            /** @var class-string<MiddlewareAttributeInterface> $attrClass */
            $attrClass = $attr->getName();
            $serialized[] = [
                'class' => $attrClass,
                'args' => $attr->getArguments(),
            ];
        }

        return $serialized;
    }

    /**
     * @param list<array{class: class-string<MiddlewareAttributeInterface>, args: array<int|string, mixed>}> $serialized
     * @return list<MiddlewareAttributeInterface>
     */
    private function hydrateMiddlewareAttributes(array $serialized): array
    {
        $instances = [];
        foreach ($serialized as $spec) {
            $attrClass = $spec['class'];
            if (!class_exists($attrClass) || !is_subclass_of($attrClass, MiddlewareAttributeInterface::class)) {
                throw new CoreException(sprintf('Compiled route references invalid middleware attribute "%s".', $attrClass));
            }
            $ref = new ReflectionClass($attrClass);
            /** @var MiddlewareAttributeInterface $instance */
            $instance = $ref->newInstanceArgs($spec['args']);
            $instances[] = $instance;
        }

        return $instances;
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
        $this->dynamicMethodChunks = null;
    }

    /**
     * Group dynamic routes per HTTP method into combined PCRE2 `(*MARK:i)` regex chunks
     * (16 routes per `preg_match()` call) while preserving exact registration order.
     *
     * @return array<string, list<array{regex: ?string, routes: array<int, array{route: Route, regex: string}>}>>
     */
    private function buildDynamicMethodChunks(): array
    {
        /** @var array<string, list<array{route: Route, regex: string}>> $byMethod */
        $byMethod = [];
        foreach ($this->dynamicRoutes as $entry) {
            foreach ($entry['route']->methods as $method) {
                $byMethod[$method][] = $entry;
            }
        }

        $compiled = [];
        foreach ($byMethod as $method => $entries) {
            $chunks = [];
            foreach (array_chunk($entries, self::DYNAMIC_CHUNK_SIZE) as $slice) {
                $branches = [];
                $indexed = [];
                $ok = true;
                foreach ($slice as $idx => $entry) {
                    $indexed[$idx] = $entry;
                    $inner = $this->stripRegexAnchorsAndNamedGroups($entry['regex']);
                    if (null === $inner) {
                        $ok = false;
                        break;
                    }
                    $branches[] = '(?:' . $inner . ')(*MARK:' . $idx . ')';
                }

                $combined = $ok ? '#\A(?:' . implode('|', $branches) . ')\z#' : null;
                if (null !== $combined && false === @preg_match($combined, '')) {
                    $combined = null;
                }

                $chunks[] = [
                    'regex' => $combined,
                    'routes' => $indexed,
                ];
            }
            $compiled[$method] = $chunks;
        }

        return $compiled;
    }

    private function stripRegexAnchorsAndNamedGroups(string $compiledRegex): ?string
    {
        if (!str_starts_with($compiledRegex, '#\\A') || !str_ends_with($compiledRegex, '\\z#')) {
            return null;
        }
        $body = substr($compiledRegex, 3, -3);
        $stripped = preg_replace('/\(\?P<[a-zA-Z_][a-zA-Z0-9_]*>/', '(?:', $body);

        return is_string($stripped) ? $stripped : null;
    }

    private function compilePathRegex(string $template): string
    {
        $parts = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*(?::[^}]+)?\})/', $template, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            return '#\A' . preg_quote($template, '#') . '\z#';
        }

        $pattern = '';
        foreach ($parts as $segment) {
            if (1 === preg_match('/\A\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}\z/', $segment, $m)) {
                $constraint = $m[2] ?? '[^/]+';
                if (str_contains($constraint, '#')) {
                    throw new CoreException(sprintf('Unsafe route parameter constraint "%s" in path "%s".', $constraint, $template));
                }
                $pattern .= sprintf('(?P<%s>%s)', $m[1], $constraint);
            } else {
                $pattern .= preg_quote($segment, '#');
            }
        }

        $compiled = '#\A' . $pattern . '\z#';
        if (false === @preg_match($compiled, '')) {
            throw new CoreException(sprintf('Invalid route regex compiled for path "%s".', $template));
        }

        return $compiled;
    }
}
