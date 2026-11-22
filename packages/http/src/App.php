<?php

declare(strict_types=1);

namespace Kongono\Http;

use Closure;
use Kongono\Core\Config;
use Kongono\Core\Container;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Events\EventDispatcher;
use Kongono\Core\Graph\ApplicationGraph;
use Kongono\Core\Graph\ServiceNode;
use Kongono\Core\Kernel;
use Kongono\Core\KernelState;
use Kongono\Http\Routing\RouteMatch;
use Kongono\Http\Routing\Router;
use Kongono\Validation\ValidationException;
use Kongono\Validation\Validator;
use Kongono\View\ViewEngine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * The Kongono v1 Application.
 *
 * Explicit, zero-magic, persistent-runtime-first entry point tying together
 * Kernel, Container, Config, Router, Middleware, Validation, Views, and ApplicationGraph.
 */
final class App
{
    private readonly Kernel $kernel;
    private readonly Container $container;
    private readonly Router $router;
    private readonly EventDispatcher $events;
    private readonly Validator $validator;
    private Config $config;
    private ?ViewEngine $views = null;

    /** @var list<MiddlewareInterface> */
    private array $middleware = [];

    private bool $debug = false;

    /**
     * Compiled per-controller-method parameter dispatch plans (zero reflection per request in persistent workers).
     *
     * @var array<string, list<array{
     *     name: string,
     *     typeName: ?string,
     *     isBuiltin: bool,
     *     allowsNull: bool,
     *     hasDefault: bool,
     *     default: mixed,
     *     kind: 'request'|'security_attr'|'builtin'|'container'|'dto'|'autowire'|'untyped'
     * }>>
     */
    private array $handlerPlanCache = [];

    /** @var array<string, bool> */
    private array $dtoCheckCache = [];

    private function __construct(
        private readonly string $basePath,
        private readonly string $name,
    ) {
        $this->kernel = new Kernel();
        $this->container = new Container();
        $this->router = new Router();
        $this->events = new EventDispatcher();
        $this->validator = new Validator();
        $this->config = new Config();

        $this->container->instance(self::class, $this);
        $this->container->instance(Kernel::class, $this->kernel);
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(Router::class, $this->router);
        $this->container->instance(EventDispatcher::class, $this->events);
        $this->container->instance(Validator::class, $this->validator);
        $this->container->instance(Config::class, $this->config);
    }

    public static function create(string $basePath, string $name = 'Kongono App'): self
    {
        $resolved = realpath($basePath);

        return new self(false !== $resolved ? $resolved : $basePath, $name);
    }

    /** @param array<string, mixed> $values */
    public function config(array $values): self
    {
        $this->config = new Config([...$this->config->all(), ...$values]);
        $this->debug = $this->config->bool('app.debug', $this->debug);
        $this->container->instance(Config::class, $this->config);

        return $this;
    }

    public function debug(bool $enabled = true): self
    {
        $this->debug = $enabled;

        return $this;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function views(?string $viewsPath = null, ?string $cachePath = null, bool $autoReload = true): self
    {
        $path = $viewsPath ?? ($this->basePath . DIRECTORY_SEPARATOR . 'views');
        $cache = $cachePath ?? ($this->basePath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'views');
        $this->views = new ViewEngine($path, $cache, autoReload: $autoReload);
        $this->container->instance(ViewEngine::class, $this->views);

        return $this;
    }

    /**
     * @param class-string|string                 $id
     * @param (Closure(Container): mixed)|class-string $concrete
     */
    public function bind(string $id, Closure|string $concrete): self
    {
        $this->container->bind($id, $concrete);
        $this->handlerPlanCache = [];
        $this->dtoCheckCache = [];

        return $this;
    }

    /**
     * @param class-string|string                 $id
     * @param (Closure(Container): mixed)|class-string $concrete
     */
    public function singleton(string $id, Closure|string $concrete): self
    {
        $this->container->singleton($id, $concrete);
        $this->handlerPlanCache = [];
        $this->dtoCheckCache = [];

        return $this;
    }

    /**
     * @param class-string|string                 $id
     * @param (Closure(Container): mixed)|class-string $concrete
     */
    public function scoped(string $id, Closure|string $concrete): self
    {
        $this->container->scoped($id, $concrete);
        $this->handlerPlanCache = [];
        $this->dtoCheckCache = [];

        return $this;
    }

    public function instance(string $id, mixed $value): self
    {
        if (ViewEngine::class === $id && $value instanceof ViewEngine) {
            $this->views = $value;
        }
        $this->container->instance($id, $value);
        $this->handlerPlanCache = [];
        $this->dtoCheckCache = [];

        return $this;
    }

    public function middleware(MiddlewareInterface ...$middleware): self
    {
        array_push($this->middleware, ...$middleware);

        return $this;
    }

    /** @param Closure(Router): void $callback */
    public function routes(Closure $callback): self
    {
        $callback($this->router);

        return $this;
    }

    /** @param class-string $controllerClass */
    public function controller(string $controllerClass): self
    {
        $this->router->controller($controllerClass);

        return $this;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function events(): EventDispatcher
    {
        return $this->events;
    }

    public function kernel(): Kernel
    {
        return $this->kernel;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    /** Render a compiled template directly into an HTML Response. */
    /** @param array<string, mixed> $data */
    public function view(string $template, array $data = [], int $status = 200): Response
    {
        if (null === $this->views) {
            throw new CoreException('ViewEngine is not configured. Call $app->views() during bootstrap.');
        }

        return Response::html($this->views->render($template, $data), $status);
    }

    /** Build the live machine-readable ApplicationGraph for CLI & Oshie/Chie/Hantei. */
    public function graph(): ApplicationGraph
    {
        $services = [];
        foreach ($this->container->registeredBindings() as $id => $lifetime) {
            $services[] = new ServiceNode($id, $lifetime);
        }

        $middlewareNames = array_map(static fn(MiddlewareInterface $m): string => $m::class, $this->middleware);
        $viewList = null !== $this->views ? $this->views->listTemplates() : [];

        return new ApplicationGraph(
            appName: $this->name,
            basePath: $this->basePath,
            routes: $this->router->routeNodes(),
            services: $services,
            middleware: $middlewareNames,
            views: $viewList,
            configKeys: $this->config->topLevelKeys(),
        );
    }

    public function boot(): self
    {
        if (KernelState::Unbooted === $this->kernel->state()) {
            $this->kernel->boot();
        }

        return $this;
    }

    public function shutdown(): void
    {
        if (KernelState::Booted === $this->kernel->state()) {
            $this->kernel->shutdown();
        }
    }

    /** Handle one HTTP request inside an isolated Kernel operation scope. */
    public function handle(Request $request): ResponseInterface
    {
        $this->boot();

        return $this->kernel->runOperation(function () use ($request): ResponseInterface {
            try {
                $terminal = new readonly class ($this) implements RequestHandlerInterface {
                    public function __construct(private App $app) {}

                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        $req = $request instanceof Request ? $request : $this->app->wrapServerRequest($request);

                        return $this->app->dispatchSafely($req);
                    }
                };

                $pipeline = new MiddlewarePipeline($terminal, ...$this->middleware);

                return $pipeline->handle($request);
            } catch (ValidationException $e) {
                return $this->renderValidationError($request, $e);
            } catch (HttpException $e) {
                return $this->renderHttpError($request, $e);
            } catch (Throwable $e) {
                return $this->renderUnexpectedError($request, $e);
            } finally {
                $this->container->resetScope();
            }
        });
    }

    /** Standard SAPI / PHP-FPM / built-in dev server entry point. */
    public function run(): void
    {
        $emitter = new ResponseEmitter();
        try {
            $request = Request::fromGlobals();
            $response = $this->handle($request);
        } catch (HttpException $e) {
            $response = Response::json(['error' => ['status' => $e->statusCode, 'message' => SecretScrubber::scrub($e->getMessage())]], $e->statusCode, $e->headers);
        } catch (Throwable) {
            $response = Response::json(['error' => ['status' => 400, 'message' => 'Malformed HTTP request.']], 400);
        }
        $emitter->emit($response);
        $this->shutdown();
    }

    /**
     * Persistent worker loop (FrankenPHP / RoadRunner / long-lived worker pattern).
     *
     * @param Closure(Closure(): void): bool $workerLoop Invokes the request handler callback per request; returns false to exit.
     */
    public function runWorker(Closure $workerLoop, ?int $maxRequests = null): void
    {
        $this->boot();
        $emitter = new ResponseEmitter();
        $handled = 0;

        try {
            $handler = function () use ($emitter): void {
                try {
                    $request = Request::fromGlobals();
                    $response = $this->handle($request);
                } catch (HttpException $e) {
                    $response = Response::json(['error' => ['status' => $e->statusCode, 'message' => SecretScrubber::scrub($e->getMessage())]], $e->statusCode, $e->headers);
                } catch (Throwable) {
                    $response = Response::json(['error' => ['status' => 400, 'message' => 'Malformed HTTP request.']], 400);
                }
                $emitter->emit($response);
            };

            while ($workerLoop($handler)) {
                ++$handled;
                if (null !== $maxRequests && $handled >= $maxRequests) {
                    break;
                }
            }
        } finally {
            $this->shutdown();
        }
    }

    /** @internal */
    public function dispatchSafely(Request $request): ResponseInterface
    {
        try {
            return $this->dispatch($request);
        } catch (ValidationException $e) {
            return $this->renderValidationError($request, $e);
        } catch (HttpException $e) {
            return $this->renderHttpError($request, $e);
        } catch (Throwable $e) {
            return $this->renderUnexpectedError($request, $e);
        }
    }

    /** @internal */
    public function wrapServerRequest(ServerRequestInterface $psr): Request
    {
        $headers = [];
        foreach ($psr->getHeaders() as $name => $values) {
            $headers[$name] = array_values($values);
        }

        return new Request(
            method: $psr->getMethod(),
            uri: $psr->getUri(),
            headers: $headers,
            body: $psr->getBody(),
            protocolVersion: $psr->getProtocolVersion(),
            serverParams: $psr->getServerParams(),
            cookieParams: $psr->getCookieParams(),
            queryParams: $psr->getQueryParams(),
            uploadedFiles: $psr->getUploadedFiles(),
            parsedBody: $psr->getParsedBody(),
            attributes: $psr->getAttributes(),
        );
    }

    /** @internal */
    public function dispatch(Request $request): ResponseInterface
    {
        $match = $this->router->match($request->method(), $request->path());
        $enriched = $request
            ->withAttribute('_route_params', $match->params)
            ->withAttribute('_route_pattern', $match->route->path);

        $routeTerminal = new readonly class ($this, $match) implements RequestHandlerInterface {
            public function __construct(
                private App $app,
                private RouteMatch $match,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->app->invokeRouteHandler(
                    $this->match,
                    $request instanceof Request ? $request : $this->app->wrapServerRequest($request),
                );
            }
        };

        $routeMiddleware = $match->route->routeMiddleware();
        foreach ($match->route->routeMiddlewareAttributes() as $middlewareAttr) {
            $routeMiddleware[] = $middlewareAttr->toMiddleware($this->container);
        }

        if ([] === $routeMiddleware) {
            return $routeTerminal->handle($enriched);
        }

        return (new MiddlewarePipeline($routeTerminal, ...$routeMiddleware))->handle($enriched);
    }

    /** @internal */
    public function invokeRouteHandler(RouteMatch $match, Request $request): ResponseInterface
    {
        $handler = $match->route->handler;

        if ($handler instanceof Closure) {
            $cacheKey = 'closure:' . spl_object_id($handler);
            $plan = $this->handlerPlanCache[$cacheKey] ??= $this->compileHandlerPlan(new ReflectionFunction($handler));
            $args = $this->resolveHandlerArgsFromPlan($plan, $request, $match->params);
            $result = $handler(...$args);
        } else {
            [$controllerClass, $methodName] = $handler;
            $controller = $this->container->get($controllerClass);
            if (!is_object($controller)) {
                throw new CoreException(sprintf('Controller "%s" did not resolve to an object.', $controllerClass));
            }
            $cacheKey = $controllerClass . '::' . $methodName;
            $plan = $this->handlerPlanCache[$cacheKey] ??= $this->compileHandlerPlan(new ReflectionMethod($controller, $methodName));
            $args = $this->resolveHandlerArgsFromPlan($plan, $request, $match->params);
            $callable = [$controller, $methodName];
            if (!is_callable($callable)) {
                throw new CoreException(sprintf('Controller method "%s::%s" is not callable.', $controllerClass, $methodName));
            }
            $result = $callable(...$args);
        }

        if ($result instanceof ResponseInterface) {
            return $result;
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        if (is_string($result)) {
            return Response::html($result);
        }

        throw new CoreException(sprintf('Route handler returned unsupported type %s.', get_debug_type($result)));
    }

    /**
     * Compile a route handler's parameter resolution plan once per persistent worker.
     *
     * @return list<array{
     *     name: string,
     *     typeName: ?string,
     *     isBuiltin: bool,
     *     allowsNull: bool,
     *     hasDefault: bool,
     *     default: mixed,
     *     kind: 'request'|'security_attr'|'builtin'|'container'|'dto'|'autowire'|'untyped'
     * }>
     */
    private function compileHandlerPlan(ReflectionFunctionAbstract $reflection): array
    {
        $plan = [];
        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            $hasDefault = $parameter->isDefaultValueAvailable();
            $default = $hasDefault ? $parameter->getDefaultValue() : null;

            if (!$type instanceof ReflectionNamedType) {
                $plan[] = [
                    'name' => $name,
                    'typeName' => null,
                    'isBuiltin' => false,
                    'allowsNull' => true,
                    'hasDefault' => $hasDefault,
                    'default' => $default,
                    'kind' => 'untyped',
                ];
                continue;
            }

            $typeName = $type->getName();
            $isBuiltin = $type->isBuiltin();
            $allowsNull = $type->allowsNull();

            if (Request::class === $typeName || ServerRequestInterface::class === $typeName) {
                $kind = 'request';
            } elseif ('Kongono\\Security\\Principal' === $typeName || 'Kongono\\Http\\Session\\Session' === $typeName) {
                $kind = 'security_attr';
            } elseif ($isBuiltin) {
                $kind = 'builtin';
            } elseif ($this->container->hasBinding($typeName)) {
                $kind = 'container';
            } elseif (class_exists($typeName) && $this->looksLikeInputDtoCached($typeName)) {
                $kind = 'dto';
            } elseif ($this->container->has($typeName)) {
                $kind = 'autowire';
            } else {
                $kind = 'untyped';
            }

            $plan[] = [
                'name' => $name,
                'typeName' => $typeName,
                'isBuiltin' => $isBuiltin,
                'allowsNull' => $allowsNull,
                'hasDefault' => $hasDefault,
                'default' => $default,
                'kind' => $kind,
            ];
        }

        return $plan;
    }

    /**
     * @param list<array{
     *     name: string,
     *     typeName: ?string,
     *     isBuiltin: bool,
     *     allowsNull: bool,
     *     hasDefault: bool,
     *     default: mixed,
     *     kind: 'request'|'security_attr'|'builtin'|'container'|'dto'|'autowire'|'untyped'
     * }> $plan
     * @param array<string, string> $routeParams
     * @return list<mixed>
     */
    private function resolveHandlerArgsFromPlan(array $plan, Request $request, array $routeParams): array
    {
        $args = [];
        foreach ($plan as $spec) {
            $name = $spec['name'];
            $typeName = $spec['typeName'];

            if ('request' === $spec['kind']) {
                $args[] = $request;
                continue;
            }

            // Inject request attributes by exact type (e.g. Session, Principal, custom middleware attrs)
            if (null !== $typeName && !$spec['isBuiltin']) {
                $attrValue = $request->getAttribute($typeName);
                if (is_object($attrValue) && $attrValue instanceof $typeName) {
                    $args[] = $attrValue;
                    continue;
                }
            }

            if ('security_attr' === $spec['kind']) {
                if ($spec['hasDefault']) {
                    $args[] = $spec['default'];
                    continue;
                }
                if ($spec['allowsNull']) {
                    $args[] = null;
                    continue;
                }
                if ('Kongono\\Security\\Principal' === $typeName) {
                    throw new HttpException(401, 'Authentication required.');
                }

                throw new CoreException('Session attribute is missing: mount SessionMiddleware before accessing Session.');
            }

            if ('builtin' === $spec['kind'] && null !== $typeName) {
                if (array_key_exists($name, $routeParams)) {
                    $args[] = $this->coerceRouteParam($routeParams[$name], $typeName, $name);
                    continue;
                }
                if ($spec['hasDefault']) {
                    $args[] = $spec['default'];
                    continue;
                }

                throw new HttpException(400, sprintf('Missing required parameter "%s".', $name));
            }

            if ('container' === $spec['kind'] && null !== $typeName) {
                $args[] = $this->container->get($typeName);
                continue;
            }

            if ('dto' === $spec['kind'] && null !== $typeName && class_exists($typeName)) {
                $args[] = $this->validator->validate($typeName, [...$request->allInput(), ...$routeParams]);
                continue;
            }

            if ('autowire' === $spec['kind'] && null !== $typeName) {
                $args[] = $this->container->get($typeName);
                continue;
            }

            if (array_key_exists($name, $routeParams)) {
                $args[] = $routeParams[$name];
                continue;
            }
            if ($spec['hasDefault']) {
                $args[] = $spec['default'];
                continue;
            }

            throw new CoreException(sprintf('Unable to resolve route parameter "$%s".', $name));
        }

        return $args;
    }

    /** @param class-string $className */
    private function looksLikeInputDtoCached(string $className): bool
    {
        return $this->dtoCheckCache[$className] ??= $this->looksLikeInputDto($className);
    }

    /**
     * @param class-string              $className
     * @param array<class-string, true> $visited
     */
    private function looksLikeInputDto(string $className, array $visited = [], int $depth = 0): bool
    {
        // Never hydrate framework/security classes from untrusted HTTP input, and prevent cyclic recursion DoS
        if ((str_starts_with($className, 'Kongono\\') && !str_contains($className, '\\Tests\\')) || $depth > 16) {
            return false;
        }
        if (isset($visited[$className])) {
            return true;
        }

        $ref = new \ReflectionClass($className);
        if (!$ref->isInstantiable() || $ref->isInternal()) {
            return false;
        }
        $ctor = $ref->getConstructor();
        if (null === $ctor || 0 === $ctor->getNumberOfParameters()) {
            return false;
        }

        $visited[$className] = true;
        $hasRequiredOrValidatedField = false;
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType) {
                return false;
            }

            $isDtoFieldType = false;
            if ($type->isBuiltin()) {
                $isDtoFieldType = true;
            } else {
                $paramTypeName = $type->getName();
                if (is_subclass_of($paramTypeName, \BackedEnum::class)) {
                    $isDtoFieldType = true;
                } elseif (
                    class_exists($paramTypeName)
                    && !$this->container->hasBinding($paramTypeName)
                    && $this->looksLikeInputDto($paramTypeName, $visited, $depth + 1)
                ) {
                    $isDtoFieldType = true;
                }
            }

            if (!$isDtoFieldType) {
                return false;
            }

            if (!$param->isDefaultValueAvailable()) {
                $hasRequiredOrValidatedField = true;
            } else {
                foreach ($param->getAttributes() as $attr) {
                    if (str_starts_with($attr->getName(), 'Kongono\\Validation\\Attributes\\')) {
                        $hasRequiredOrValidatedField = true;
                        break;
                    }
                }
            }
        }

        return $hasRequiredOrValidatedField;
    }

    private function coerceRouteParam(string $value, string $typeName, string $name): mixed
    {
        return match ($typeName) {
            'int' => 1 === preg_match('/\A-?\d+\z/', $value)
                ? (int) $value
                : throw new HttpException(400, sprintf('Route parameter "%s" must be an integer.', $name)),
            'float' => is_numeric($value)
                ? (float) $value
                : throw new HttpException(400, sprintf('Route parameter "%s" must be numeric.', $name)),
            'bool' => in_array(strtolower($value), ['1', 'true', 'yes'], true),
            default => $value,
        };
    }

    private function renderValidationError(Request $request, ValidationException $e): Response
    {
        if ($request->expectsJson()) {
            return Response::json($e->toArray(), 422);
        }

        $items = '';
        foreach ($e->errors as $field => $messages) {
            foreach ($messages as $msg) {
                $items .= sprintf('<li><strong>%s</strong>: %s</li>', htmlspecialchars($field, ENT_QUOTES, 'UTF-8'), htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'));
            }
        }

        return Response::html(
            sprintf('<!doctype html><html><body><h1>Validation Failed</h1><ul>%s</ul></body></html>', $items),
            422,
        );
    }

    private function renderHttpError(Request $request, HttpException $e): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'error' => 'http_error',
                'status' => $e->statusCode,
                'message' => SecretScrubber::scrub($e->getMessage()),
            ], $e->statusCode, $e->headers);
        }

        return Response::html(
            sprintf(
                '<!doctype html><html><body><h1>%d</h1><p>%s</p></body></html>',
                $e->statusCode,
                htmlspecialchars(SecretScrubber::scrub($e->getMessage()), ENT_QUOTES, 'UTF-8'),
            ),
            $e->statusCode,
            $e->headers,
        );
    }

    private function renderUnexpectedError(Request $request, Throwable $e): Response
    {
        $safeMessage = $this->debug
            ? SecretScrubber::scrub($e->getMessage())
            : 'An internal server error occurred.';

        $payload = [
            'error' => 'internal_server_error',
            'message' => $safeMessage,
        ];

        if ($e instanceof CoreException && null !== $e->diagnostic) {
            $payload['diagnostic'] = $e->diagnostic->toSafeArray();
        }

        if ($request->expectsJson()) {
            return Response::json($payload, 500);
        }

        if ($this->debug) {
            return Response::html((new DevErrorRenderer())->render($request, $e), 500);
        }

        return Response::html(
            '<!doctype html><html><body><h1>500 Internal Server Error</h1><p>An internal server error occurred.</p></body></html>',
            500,
        );
    }
}
