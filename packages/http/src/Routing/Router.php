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

    /** Matches `{name}` or `{name:constraint}` including single-level `{m,n}` / `\p{L}` braces inside constraints. */
    private const string PARAM_TOKEN_SPLIT_REGEX = '/(\{[a-zA-Z_][a-zA-Z0-9_]*(?::(?:[^{}]++|\{[^{}]*+\})++)?\})/';

    private const string PARAM_TOKEN_CAPTURE_REGEX = '/\A\{([a-zA-Z_][a-zA-Z0-9_]*)(?::((?:[^{}]++|\{[^{}]*+\})++))?\}\z/';

    private const string PARAM_TOKEN_REPLACE_REGEX = '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::(?:[^{}]++|\{[^{}]*+\})++)?\}/';

    /** @var list<Route> */
    private array $routes = [];

    /** @var array<string, array<string, Route>> Static path -> HTTP method -> Route (O(1) lookup) */
    private array $staticRoutes = [];

    /** @var list<array{route: Route, regex: string}> Pre-compiled dynamic parameter routes */
    private array $dynamicRoutes = [];

    /** @var array<string, array<string, array<string, Route>>> Exact host -> static path -> HTTP method -> Route (O(1) lookup) */
    private array $domainStaticRoutes = [];

    /** @var list<array{route: Route, domain: string, domainRegex: ?string, pathRegex: ?string}> Domain-scoped dynamic or wildcard-host routes */
    private array $domainRoutes = [];

    /**
     * Method-indexed group-chunked PCRE2 (*MARK:idx) regexes for sub-linear dynamic route dispatch.
     *
     * @var ?array<string, list<array{regex: ?string, routes: array<int, array{route: Route, regex: string}>}>>
     */
    private ?array $dynamicMethodChunks = null;

    private string $groupPrefix = '';

    private ?string $groupDomain = null;

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
    public function add(array $methods, string $path, Closure|array $handler, ?string $domain = null): Route
    {
        $normalizedMethods = array_values(array_unique(array_map('strtoupper', $methods)));
        $fullPath = $this->normalizePath($this->groupPrefix . '/' . ltrim($path, '/'));
        $effectiveDomain = null !== $domain
            ? $this->normalizeDomainTemplate($domain)
            : $this->groupDomain;
        $route = new Route($normalizedMethods, $fullPath, $handler, domain: $effectiveDomain);
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
     * Group routes under a host/domain constraint (exact host such as `"bbl.to"` or parameterized domain
     * such as `"{tenant}.bidbylink.com"`) with optional shared path prefix and middleware.
     *
     * @param Closure(self): void       $callback
     * @param list<MiddlewareInterface> $middleware
     */
    public function domain(string $host, Closure $callback, string $prefix = '', array $middleware = []): self
    {
        $previousDomain = $this->groupDomain;
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupDomain = $this->normalizeDomainTemplate($host);
        if ('' !== $prefix) {
            $this->groupPrefix = $this->normalizePath($previousPrefix . '/' . ltrim($prefix, '/'));
            if ('/' === $this->groupPrefix) {
                $this->groupPrefix = '';
            }
        }
        $this->groupMiddleware = [...$previousMiddleware, ...$middleware];

        try {
            $callback($this);
        } finally {
            $this->groupDomain = $previousDomain;
            $this->groupPrefix = $previousPrefix;
            $this->groupMiddleware = $previousMiddleware;
        }

        return $this;
    }

    /**
     * Register all methods decorated with #[Get], #[Post], #[Put], #[Patch], #[Delete] on a controller class.
     * Supports class-level #[Prefix], class- or method-level #[Domain], plus MiddlewareAttributeInterface attributes
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

        $classDomain = null;
        $domainAttrs = $reflection->getAttributes(Domain::class);
        if ([] !== $domainAttrs) {
            $classDomain = $domainAttrs[0]->newInstance()->host;
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            if ([] === $attributes) {
                continue;
            }

            $methodDomainAttrs = $method->getAttributes(Domain::class);
            $effectiveDomain = [] !== $methodDomainAttrs
                ? $methodDomainAttrs[0]->newInstance()->host
                : $classDomain;

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $fullPath = '' !== $classPrefix
                    ? $classPrefix . '/' . ltrim($instance->path, '/')
                    : $instance->path;
                $route = $this->add($instance->methods, $fullPath, [$controllerClass, $method->getName()], domain: $effectiveDomain);
                if (null !== $instance->name) {
                    $route->name($instance->name);
                }
            }
        }

        return $this;
    }

    /**
     * Match a method, path, and optional request host using O(1) static maps + pre-compiled regexes,
     * with deterministic precedence (domain-scoped routes take priority over global routes) and proper 404 / 405 detection.
     */
    public function match(string $method, string $path, ?string $host = null): RouteMatch
    {
        $upperMethod = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);
        $normalizedHost = null !== $host ? self::normalizeRequestHost($host) : null;
        $allowedMethods = [];

        // 1. Domain-scoped routes (exact host O(1) static map, then exact/wildcard host dynamic routes)
        if (null !== $normalizedHost && '' !== $normalizedHost) {
            if (isset($this->domainStaticRoutes[$normalizedHost][$normalizedPath])) {
                $staticForHost = $this->domainStaticRoutes[$normalizedHost][$normalizedPath];
                if (isset($staticForHost[$upperMethod])) {
                    return new RouteMatch($staticForHost[$upperMethod], []);
                }
                foreach (array_keys($staticForHost) as $allowed) {
                    $allowedMethods[] = $allowed;
                }
            }

            if ([] !== $this->domainRoutes) {
                foreach ($this->domainRoutes as $entry) {
                    $hostParams = [];
                    if (null === $entry['domainRegex']) {
                        if ($entry['domain'] !== $normalizedHost) {
                            continue;
                        }
                    } else {
                        if (1 !== preg_match($entry['domainRegex'], $normalizedHost, $hostMatches)) {
                            continue;
                        }
                        foreach ($hostMatches as $key => $value) {
                            if (is_string($key)) {
                                $hostParams[$key] = $value;
                            }
                        }
                    }

                    $pathParams = [];
                    if (null === $entry['pathRegex']) {
                        if ($entry['route']->path !== $normalizedPath) {
                            continue;
                        }
                    } else {
                        if (1 !== preg_match($entry['pathRegex'], $normalizedPath, $pathMatches)) {
                            continue;
                        }
                        foreach ($pathMatches as $key => $value) {
                            if (is_string($key)) {
                                $pathParams[$key] = $value;
                            }
                        }
                    }

                    if (in_array($upperMethod, $entry['route']->methods, true)) {
                        return new RouteMatch($entry['route'], [...$hostParams, ...$pathParams]);
                    }
                    array_push($allowedMethods, ...$entry['route']->methods);
                }
            }
        }

        // 2. Global (any-host) O(1) static routes
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
     *     domain_routes: list<array{
     *         domain: string,
     *         domain_regex: ?string,
     *         methods: list<string>,
     *         path: string,
     *         regex: ?string,
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
        $domainRoutes = [];
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

            $routeDomain = $route->routeDomain();
            if (null !== $routeDomain) {
                $domainRoutes[] = [
                    'domain' => $routeDomain,
                    'domain_regex' => str_contains($routeDomain, '{') ? $this->compileDomainRegex($routeDomain) : null,
                    'methods' => $route->methods,
                    'path' => $route->path,
                    'regex' => str_contains($route->path, '{') ? $this->compilePathRegex($route->path) : null,
                    'handler' => $route->handler,
                    'name' => $route->routeName(),
                    'middleware_attributes' => $serializedAttrs,
                ];
                continue;
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
            'domain_routes' => $domainRoutes,
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
     *     }>,
     *     domain_routes?: list<array{
     *         domain: string,
     *         domain_regex: ?string,
     *         methods: list<string>,
     *         path: string,
     *         regex: ?string,
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

        foreach ($compiled['domain_routes'] ?? [] as $item) {
            if (!array_key_exists('middleware_attributes', $item)) {
                throw new CoreException(sprintf('Compiled domain route "%s%s" is missing middleware_attributes metadata.', $item['domain'], $item['path']));
            }
            $normalizedMethods = array_values(array_unique(array_map('strtoupper', $item['methods'])));
            $normalizedDomain = $this->normalizeDomainTemplate($item['domain']);
            $fullPath = $this->normalizePath($item['path']);
            $route = new Route($normalizedMethods, $fullPath, $item['handler'], domain: $normalizedDomain);
            if (null !== $item['name']) {
                $route->name($item['name']);
            }
            $attrs = $this->hydrateMiddlewareAttributes($item['middleware_attributes']);
            if ([] !== $attrs) {
                $route->middlewareAttributes(...$attrs);
            }
            $this->routes[] = $route;
            if (null === $item['domain_regex'] && null === $item['regex']) {
                foreach ($route->methods as $method) {
                    $this->domainStaticRoutes[$normalizedDomain][$fullPath][$method] ??= $route;
                }
            } else {
                $this->domainRoutes[] = [
                    'route' => $route,
                    'domain' => $normalizedDomain,
                    'domainRegex' => $item['domain_regex'],
                    'pathRegex' => $item['regex'],
                ];
            }
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
     * Generate a URL from a named route, substituting path (and domain, if domain-scoped) parameters
     * and optional query string. Domain-scoped routes emit an absolute URL (e.g. `https://bbl.to/x8K2`).
     *
     * @param array<string, string|int> $params
     * @param array<string, string|int> $query
     */
    public function url(string $name, array $params = [], array $query = [], string $scheme = 'https'): string
    {
        foreach ($this->routes as $route) {
            if ($route->routeName() !== $name) {
                continue;
            }
            $builtPath = preg_replace_callback(
                self::PARAM_TOKEN_REPLACE_REGEX,
                static function (array $m) use ($name, $params): string {
                    $key = $m[1];
                    if (!array_key_exists($key, $params)) {
                        throw new HttpException(500, sprintf('Missing required route parameter "%s" for route "%s".', $key, $name));
                    }

                    return rawurlencode((string) $params[$key]);
                },
                $route->path,
            );
            $url = is_string($builtPath) ? $builtPath : $route->path;

            $routeDomain = $route->routeDomain();
            if (null !== $routeDomain) {
                $cleanScheme = strtolower(trim($scheme));
                if ('http' !== $cleanScheme && 'https' !== $cleanScheme) {
                    throw new HttpException(500, sprintf('Unsupported URL scheme "%s" for route "%s".', $scheme, $name));
                }
                $builtHost = preg_replace_callback(
                    self::PARAM_TOKEN_REPLACE_REGEX,
                    static function (array $m) use ($name, $params): string {
                        $key = $m[1];
                        if (!array_key_exists($key, $params)) {
                            throw new HttpException(500, sprintf('Missing required domain parameter "%s" for route "%s".', $key, $name));
                        }
                        $val = strtolower((string) $params[$key]);
                        if ('' === $val || 1 !== preg_match('/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/', $val)) {
                            throw new HttpException(500, sprintf('Invalid host label "%s" for parameter "%s" on route "%s".', $val, $key, $name));
                        }

                        return $val;
                    },
                    $routeDomain,
                );
                $hostPart = is_string($builtHost) ? $builtHost : $routeDomain;
                $url = $cleanScheme . '://' . $hostPart . $url;
            }

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

    private function normalizeDomainTemplate(string $host): string
    {
        $trimmed = trim($host);
        if ('' === $trimmed) {
            throw new CoreException(sprintf('Invalid route domain template "%s".', $host));
        }

        // Reject accidental scheme in domain template with actionable guidance
        if (str_contains($trimmed, '://')) {
            throw new CoreException(sprintf('Route domain template "%s" must omit scheme (use "bbl.to", not "https://bbl.to").', $host));
        }

        if (1 === preg_match('/[\x00-\x20\x7f]/', $trimmed)) {
            throw new CoreException(sprintf('Invalid route domain template "%s".', $host));
        }

        // Lowercase static host parts while preserving `{paramName:constraint}` case
        $parts = preg_split(self::PARAM_TOKEN_SPLIT_REGEX, $trimmed, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            throw new CoreException(sprintf('Invalid route domain template "%s".', $host));
        }

        $rebuilt = '';
        foreach ($parts as $segment) {
            if ('' === $segment) {
                continue;
            }
            if (1 === preg_match(self::PARAM_TOKEN_CAPTURE_REGEX, $segment)) {
                $rebuilt .= $segment;
            } else {
                $rebuilt .= strtolower($segment);
            }
        }

        $clean = rtrim($rebuilt, '.');

        // Validate static segments outside `{param:constraint}` placeholders (rejects paths, ports, `@`, `?`, `#`, `,`)
        $stripped = preg_replace(self::PARAM_TOKEN_REPLACE_REGEX, 'x', $clean);
        if (!is_string($stripped) || 1 !== preg_match('/\A[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z/', $stripped)) {
            throw new CoreException(sprintf('Invalid route domain template "%s".', $host));
        }

        return $clean;
    }

    private static function normalizeRequestHost(string $host): string
    {
        $raw = trim($host);
        if ('' === $raw || 1 === preg_match('/[\x00-\x20\x7f@\/\\\\?#,]/', $raw)) {
            return '';
        }
        if (str_starts_with($raw, '[')) {
            return 1 === preg_match('/\A(\[[0-9a-fA-F:]+\])(?::[0-9]{1,5})?\z/', $raw, $m)
                ? strtolower($m[1])
                : '';
        }
        $colonPos = strpos($raw, ':');
        if (false !== $colonPos) {
            $portPart = substr($raw, $colonPos + 1);
            if ('' === $portPart || !ctype_digit($portPart)) {
                return '';
            }
            $raw = substr($raw, 0, $colonPos);
        }
        $clean = rtrim(strtolower($raw), '.');

        return '' !== $clean && 1 === preg_match('/\A[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z/', $clean)
            ? $clean
            : '';
    }

    private function indexRoute(Route $route): void
    {
        $routeDomain = $route->routeDomain();
        if (null !== $routeDomain) {
            $hasDomainParam = str_contains($routeDomain, '{');
            $hasPathParam = str_contains($route->path, '{');

            if (!$hasDomainParam && !$hasPathParam) {
                foreach ($route->methods as $method) {
                    $this->domainStaticRoutes[$routeDomain][$route->path][$method] ??= $route;
                }

                return;
            }

            $this->domainRoutes[] = [
                'route' => $route,
                'domain' => $routeDomain,
                'domainRegex' => $hasDomainParam ? $this->compileDomainRegex($routeDomain) : null,
                'pathRegex' => $hasPathParam ? $this->compilePathRegex($route->path) : null,
            ];

            return;
        }

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

    private function compileDomainRegex(string $template): string
    {
        $parts = preg_split(self::PARAM_TOKEN_SPLIT_REGEX, $template, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            return '#\A' . preg_quote($template, '#') . '\z#';
        }

        $pattern = '';
        foreach ($parts as $segment) {
            if (1 === preg_match(self::PARAM_TOKEN_CAPTURE_REGEX, $segment, $m)) {
                $constraint = $m[2] ?? '[a-z0-9](?:[a-z0-9-]*[a-z0-9])?';
                if (str_contains($constraint, '#')) {
                    throw new CoreException(sprintf('Unsafe domain parameter constraint "%s" in host "%s".', $constraint, $template));
                }
                $pattern .= sprintf('(?P<%s>%s)', $m[1], $constraint);
            } else {
                $pattern .= preg_quote($segment, '#');
            }
        }

        $compiled = '#\A' . $pattern . '\z#';
        if (false === @preg_match($compiled, '')) {
            throw new CoreException(sprintf('Invalid domain regex compiled for host "%s".', $template));
        }

        return $compiled;
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
        $parts = preg_split(self::PARAM_TOKEN_SPLIT_REGEX, $template, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            return '#\A' . preg_quote($template, '#') . '\z#';
        }

        $pattern = '';
        foreach ($parts as $segment) {
            if (1 === preg_match(self::PARAM_TOKEN_CAPTURE_REGEX, $segment, $m)) {
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
