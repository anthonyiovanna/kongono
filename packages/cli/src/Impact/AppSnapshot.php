<?php

declare(strict_types=1);

namespace Kongono\Cli\Impact;

use Closure;
use FilesystemIterator;
use Kongono\Cli\Diagnostics\FullStackContractProver;
use Kongono\Http\App;
use Kongono\Http\Routing\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use SplFileInfo;
use Throwable;

/**
 * Deterministic semantic snapshot of a Kongono application's routes, security/capability
 * attributes, PHP 8.5 Input DTO schemas, global middleware, and `.kgn.html` views.
 *
 * Used by `kongo snapshot` and `kongo impact` to compute zero-hallucination PR blast radius.
 */
final readonly class AppSnapshot
{
    /**
     * @param list<string> $middleware
     * @param array<string, array{
     *     method: string,
     *     path: string,
     *     handler: string,
     *     attributes: list<string>,
     *     dto: ?array{class: string, fields: array<string, array{type: string, required: bool, nullable: bool}>}
     * }> $routes
     * @param list<string> $views
     */
    public function __construct(
        public string $appName,
        public string $capturedAt,
        public array $middleware,
        public array $routes,
        public array $views,
    ) {}

    public static function capture(string $appRoot, App $app): self
    {
        $graph = $app->graph();
        $prover = new FullStackContractProver();
        $routes = [];

        foreach ($app->router()->routes() as $route) {
            $attrs = self::collectRouteAttributes($route);
            $dtoSchema = self::extractDtoSchema($route, $app, $prover);

            foreach ($route->methods as $method) {
                $upper = strtoupper($method);
                if ('HEAD' === $upper) {
                    continue;
                }
                $key = $upper . ' ' . $route->path;
                $routes[$key] = [
                    'method' => $upper,
                    'path' => $route->path,
                    'handler' => $route->handlerLabel(),
                    'attributes' => $attrs,
                    'dto' => $dtoSchema,
                ];
            }
        }
        ksort($routes);

        return new self(
            appName: $graph->appName,
            capturedAt: gmdate('Y-m-d\TH:i:s\Z'),
            middleware: $graph->middleware,
            routes: $routes,
            views: self::collectViews($appRoot . DIRECTORY_SEPARATOR . 'views'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'app_name' => $this->appName,
            'captured_at' => $this->capturedAt,
            'middleware' => $this->middleware,
            'routes' => $this->routes,
            'views' => $this->views,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var list<string> $mw */
        $mw = is_array($data['middleware'] ?? null) ? array_values(array_filter($data['middleware'], 'is_string')) : [];
        /** @var list<string> $views */
        $views = is_array($data['views'] ?? null) ? array_values(array_filter($data['views'], 'is_string')) : [];

        $routes = [];
        if (is_array($data['routes'] ?? null)) {
            foreach ($data['routes'] as $sig => $info) {
                if (!is_string($sig) || !is_array($info)) {
                    continue;
                }
                /** @var list<string> $attrs */
                $attrs = is_array($info['attributes'] ?? null) ? array_values(array_filter($info['attributes'], 'is_string')) : [];
                $dto = null;
                if (is_array($info['dto'] ?? null) && is_string($info['dto']['class'] ?? null) && is_array($info['dto']['fields'] ?? null)) {
                    $fields = [];
                    foreach ($info['dto']['fields'] as $fn => $fi) {
                        if (is_string($fn) && is_array($fi)) {
                            $fields[$fn] = [
                                'type' => is_string($fi['type'] ?? null) ? $fi['type'] : 'mixed',
                                'required' => (bool) ($fi['required'] ?? false),
                                'nullable' => (bool) ($fi['nullable'] ?? false),
                            ];
                        }
                    }
                    $dto = ['class' => $info['dto']['class'], 'fields' => $fields];
                }
                $routes[$sig] = [
                    'method' => is_string($info['method'] ?? null) ? $info['method'] : 'GET',
                    'path' => is_string($info['path'] ?? null) ? $info['path'] : '/',
                    'handler' => is_string($info['handler'] ?? null) ? $info['handler'] : 'Closure',
                    'attributes' => $attrs,
                    'dto' => $dto,
                ];
            }
        }

        return new self(
            appName: is_string($data['app_name'] ?? null) ? $data['app_name'] : 'Kongono App',
            capturedAt: is_string($data['captured_at'] ?? null) ? $data['captured_at'] : '',
            middleware: $mw,
            routes: $routes,
            views: $views,
        );
    }

    /** @return list<string> */
    private static function collectRouteAttributes(Route $route): array
    {
        $names = [];
        foreach ($route->routeMiddlewareAttributes() as $a) {
            $names[] = $a::class;
        }
        if (is_array($route->handler)) {
            try {
                $rc = new ReflectionClass($route->handler[0]);
                foreach ($rc->getAttributes() as $a) {
                    $names[] = $a->getName();
                }
                if ($rc->hasMethod($route->handler[1])) {
                    foreach ($rc->getMethod($route->handler[1])->getAttributes() as $a) {
                        $names[] = $a->getName();
                    }
                }
            } catch (Throwable) {
                // ignore
            }
        } elseif ($route->handler instanceof Closure) {
            try {
                $rf = new ReflectionFunction($route->handler);
                foreach ($rf->getAttributes() as $a) {
                    $names[] = $a->getName();
                }
            } catch (Throwable) {
                // ignore
            }
        }
        $unique = array_values(array_unique($names));
        sort($unique);

        return $unique;
    }

    /**
     * @return ?array{class: string, fields: array<string, array{type: string, required: bool, nullable: bool}>}
     */
    private static function extractDtoSchema(Route $route, App $app, FullStackContractProver $prover): ?array
    {
        try {
            $ref = $route->handler instanceof Closure
                ? new ReflectionFunction($route->handler)
                : new ReflectionMethod($route->handler[0], $route->handler[1]);
        } catch (Throwable) {
            return null;
        }

        $dtoRef = $prover->findInputDtoFromReflection($ref, $app->container());
        if (null === $dtoRef) {
            return null;
        }
        $ctor = $dtoRef->getConstructor();
        if (null === $ctor) {
            return null;
        }

        $fields = [];
        foreach ($ctor->getParameters() as $p) {
            $t = $p->getType();
            $typeName = $t instanceof ReflectionNamedType ? $t->getName() : 'mixed';
            $fields[$p->getName()] = [
                'type' => $typeName,
                'required' => !$p->isDefaultValueAvailable() && !$p->allowsNull(),
                'nullable' => $p->allowsNull(),
            ];
        }
        ksort($fields);

        return [
            'class' => $dtoRef->getName(),
            'fields' => $fields,
        ];
    }

    /** @return list<string> */
    private static function collectViews(string $viewsDir): array
    {
        if (!is_dir($viewsDir)) {
            return [];
        }
        $views = [];
        $real = realpath($viewsDir);
        $base = rtrim(str_replace('\\', '/', false !== $real ? $real : $viewsDir), '/') . '/';
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), '.kgn.html')) {
                continue;
            }
            $norm = str_replace('\\', '/', $file->getPathname());
            if (str_starts_with($norm, $base)) {
                $views[] = substr($norm, strlen($base), -strlen('.kgn.html'));
            }
        }
        sort($views);

        return $views;
    }
}
