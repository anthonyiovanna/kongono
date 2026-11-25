<?php

declare(strict_types=1);

namespace Kongono\Http;

use Closure;
use Kongono\Core\Clock\ClockInterface;
use Kongono\Core\Clock\SystemClock;
use Kongono\Core\Config;
use Kongono\Core\Container;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\ErrorExplainer;
use Kongono\Core\Diagnostics\ErrorExplanation;
use Kongono\Core\Diagnostics\ErrorReference;
use Kongono\Core\Diagnostics\FileIncidentStore;
use Kongono\Core\Diagnostics\IncidentRecord;
use Kongono\Core\Diagnostics\IncidentStoreInterface;
use Kongono\Core\Diagnostics\RequestTrace;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Events\EventDispatcher;
use Kongono\Core\Graph\ApplicationGraph;
use Kongono\Core\Graph\ServiceNode;
use Kongono\Core\Kernel;
use Kongono\Core\KernelState;
use Kongono\Http\Routing\RouteMatch;
use Kongono\Http\Routing\Router;
use Kongono\Http\Session\Session;
use Kongono\Validation\RuleInterface;
use Kongono\Validation\ValidatableDtoInterface;
use Kongono\Validation\ValidationException;
use Kongono\Validation\Validator;
use Kongono\View\ViewEngine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
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
        $this->container->instance(ClockInterface::class, new SystemClock());
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

    /**
     * Enable Kongono's Production Incident Time-Machine, recording secret-scrubbed 5W1H snapshots
     * indexed by each response's `KGN-YYYYMMDD-XXXXXXXX` Reference ID for `kongo incident:replay`.
     */
    public function recordIncidents(?IncidentStoreInterface $store = null): self
    {
        $resolved = $store ?? new FileIncidentStore($this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'incidents');
        $this->container->instance(IncidentStoreInterface::class, $resolved);

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
        $startedNs = hrtime(true);
        $tracedRequest = $request->withAttribute('_kgn_start_ns', $startedNs);

        return $this->kernel->runOperation(function () use ($tracedRequest, $startedNs): ResponseInterface {
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
                $response = $pipeline->handle($tracedRequest);
            } catch (ValidationException $e) {
                $response = $this->renderValidationError($tracedRequest, $e, $startedNs);
            } catch (HttpException $e) {
                $response = $this->renderHttpError($tracedRequest, $e, $startedNs);
            } catch (Throwable $e) {
                $response = $this->renderUnexpectedError($tracedRequest, $e, $startedNs);
            } finally {
                $this->container->resetScope();
            }

            // RFC 9110 §9.3.2: HEAD responses MUST NOT transmit a message body (including on error paths)
            if ('HEAD' === strtoupper($tracedRequest->getMethod())) {
                if (!$response->hasHeader('Content-Length') && !($response instanceof Response && $response->isStreaming())) {
                    $size = $response->getBody()->getSize();
                    if (null !== $size) {
                        $response = $response->withHeader('Content-Length', (string) $size);
                    }
                }
                $response->getBody()->close();
                $response = $response->withBody(Stream::fromString(''));
            }

            return $response;
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
        $startAttr = $request->getAttribute('_kgn_start_ns');
        $startedNs = is_int($startAttr) ? $startAttr : hrtime(true);
        $matchedRoute = null;
        $matchedHandler = null;
        /** @var array<string, string> $matchedParams */
        $matchedParams = [];

        try {
            $match = $this->router->match($request->method(), $request->path());
            $matchedRoute = strtoupper($request->method()) . ' ' . $match->route->path;
            $matchedHandler = $match->route->handlerLabel();
            $matchedParams = $match->params;

            return $this->dispatchMatched($request, $match);
        } catch (ValidationException $e) {
            return $this->renderValidationError($request, $e, $startedNs, $matchedRoute, $matchedHandler, $matchedParams);
        } catch (HttpException $e) {
            return $this->renderHttpError($request, $e, $startedNs, $matchedRoute, $matchedHandler, $matchedParams);
        } catch (Throwable $e) {
            return $this->renderUnexpectedError($request, $e, $startedNs, $matchedRoute, $matchedHandler, $matchedParams);
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

    private function dispatchMatched(Request $request, RouteMatch $match): ResponseInterface
    {
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
                    $args[] = is_object($spec['default']) ? clone $spec['default'] : $spec['default'];
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
                    $args[] = is_object($spec['default']) ? clone $spec['default'] : $spec['default'];
                    continue;
                }

                throw new HttpException(400, sprintf('Missing required parameter "%s".', $name));
            }

            if ('container' === $spec['kind'] && null !== $typeName) {
                $resolved = $this->container->get($typeName);
                if ($resolved instanceof ViewEngine) {
                    $resolved = $this->bindRequestFlashToView($resolved, $request);
                }
                $args[] = $resolved;
                continue;
            }

            if ('dto' === $spec['kind'] && null !== $typeName && class_exists($typeName)) {
                $args[] = $this->validator->validate($typeName, [...$request->dtoInput(), ...$routeParams]);
                continue;
            }

            if ('autowire' === $spec['kind'] && null !== $typeName) {
                $resolved = $this->container->get($typeName);
                if ($resolved instanceof ViewEngine) {
                    $resolved = $this->bindRequestFlashToView($resolved, $request);
                }
                $args[] = $resolved;
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
     * Only explicitly opted-in DTOs (implementing ValidatableDtoInterface or carrying at least one
     * Kongono validation attribute / RuleInterface) may be implicitly hydrated from HTTP input.
     * Prevents forging arbitrary userland value objects (e.g. TenantContext, CurrentUser) when
     * an auth/tenant middleware is omitted.
     *
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
        $hasExplicitValidationMarker = $ref->implementsInterface(ValidatableDtoInterface::class);
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

            if (!$hasExplicitValidationMarker) {
                foreach ($param->getAttributes() as $attr) {
                    $attrName = $attr->getName();
                    if (str_starts_with($attrName, 'Kongono\\Validation\\Attributes\\') || is_subclass_of($attrName, RuleInterface::class)) {
                        $hasExplicitValidationMarker = true;
                        break;
                    }
                }
                if (!$hasExplicitValidationMarker && !$param->isPromoted() && $ref->hasProperty($param->getName())) {
                    foreach ($ref->getProperty($param->getName())->getAttributes() as $attr) {
                        $attrName = $attr->getName();
                        if (str_starts_with($attrName, 'Kongono\\Validation\\Attributes\\') || is_subclass_of($attrName, RuleInterface::class)) {
                            $hasExplicitValidationMarker = true;
                            break;
                        }
                    }
                }
            }
        }

        return $hasExplicitValidationMarker;
    }

    private function coerceRouteParam(string $value, string $typeName, string $name): mixed
    {
        if ('int' === $typeName) {
            if (1 === preg_match('/\A-?\d+\z/', $value)) {
                $validated = filter_var($value, FILTER_VALIDATE_INT);
                if (false !== $validated) {
                    return $validated;
                }
            }

            throw new HttpException(400, sprintf('Route parameter "%s" must be an integer.', $name));
        }

        return match ($typeName) {
            'float' => is_numeric($value) && is_finite((float) $value)
                ? (float) $value
                : throw new HttpException(400, sprintf('Route parameter "%s" must be numeric.', $name)),
            'bool' => in_array(strtolower($value), ['1', 'true', 'yes'], true),
            default => $value,
        };
    }

    private function bindRequestFlashToView(ViewEngine $views, Request $request): ViewEngine
    {
        $session = $request->getAttribute(Session::class);
        if (!$session instanceof Session) {
            return $views;
        }
        $errors = $session->validationErrors();
        $old = $session->oldInput();
        if ([] === $errors && [] === $old) {
            return $views;
        }

        return $views->withFlash($errors, $old);
    }

    /**
     * @param array<string, string> $matchedParams
     */
    private function renderValidationError(
        Request $request,
        ValidationException $e,
        ?int $startedNs = null,
        ?string $matchedRoute = null,
        ?string $matchedHandler = null,
        array $matchedParams = [],
    ): Response {
        $referenceId = ErrorReference::generate();
        $explanation = $this->debug
            ? (new ErrorExplainer())->explainHttpStatus(
                statusCode: 422,
                message: 'Please review the highlighted fields and try again.',
                method: $request->method(),
                path: $request->path(),
                availableRoutes: $this->registeredRouteSignatures(),
                validationErrors: $e->errors,
            )
            : null;
        $trace = $this->debug
            ? $this->buildRequestTrace($request, $referenceId, $startedNs, $matchedRoute, $matchedHandler, $matchedParams)
            : null;

        if ($request->isBridge()) {
            $body = [...$e->toArray(), 'errors' => $e->errors, 'reference' => $referenceId];
            if (null !== $explanation) {
                $body['explanation'] = $explanation->toArray();
            }
            if (null !== $trace) {
                $body['execution_trace'] = $trace->toArray();
            }

            return Response::json($body, 422, [
                'X-Kongono-Bridge' => 'true',
                'Vary' => 'X-Kongono-Bridge',
                'X-Kongono-Error-Id' => $referenceId,
            ]);
        }
        if ($request->expectsJson()) {
            $body = [...$e->toArray(), 'reference' => $referenceId];
            if (null !== $explanation) {
                $body['explanation'] = $explanation->toArray();
            }
            if (null !== $trace) {
                $body['execution_trace'] = $trace->toArray();
            }

            return Response::json($body, 422, ['X-Kongono-Error-Id' => $referenceId]);
        }

        // Standard browser HTML form submission with active Session and verified same-origin Referer:
        // flash validation errors + secret-scrubbed old() input and issue 303 See Other PRG redirect.
        if (!$request->isLive()) {
            $session = $request->getAttribute(Session::class);
            if ($session instanceof Session) {
                $safeBack = $this->resolveSafeSameOriginReferer($request);
                if (null !== $safeBack) {
                    $session->flashValidation($e->errors, $request->allInput());

                    return Response::redirect($safeBack, 303);
                }
            }
        }

        $custom = $this->tryRenderCustomErrorView(422, 'Validation Failed', 'Please review the highlighted fields and try again.', $request, $e->errors);
        if (null !== $custom) {
            return Response::html($custom, 422, ['X-Kongono-Error-Id' => $referenceId]);
        }

        return Response::html(
            (new ErrorPageRenderer())->render(
                statusCode: 422,
                message: 'Please review the highlighted fields and try again.',
                request: $request,
                validationErrors: $e->errors,
                debug: $this->debug,
                appName: $this->name,
                explanation: $explanation,
                referenceId: $referenceId,
                trace: $trace,
            ),
            422,
            ['X-Kongono-Error-Id' => $referenceId],
        );
    }

    /**
     * Validate that the `Referer` header points to the exact same origin as the request and
     * normalize it to an origin-relative `/path?query` target (preventing open redirects).
     */
    private function resolveSafeSameOriginReferer(Request $request): ?string
    {
        $referer = trim($request->getHeaderLine('Referer'));
        if ('' === $referer || 1 === preg_match('/[\x00-\x1f\x7f]/', $referer)) {
            return null;
        }

        if (str_starts_with($referer, '/')) {
            if (str_starts_with($referer, '//') || str_starts_with($referer, '/\\')) {
                return null;
            }

            return $referer;
        }

        $parsed = parse_url($referer);
        if (!is_array($parsed) || isset($parsed['user']) || isset($parsed['pass'])) {
            return null;
        }

        $scheme = is_string($parsed['scheme'] ?? null) ? strtolower($parsed['scheme']) : '';
        $host = is_string($parsed['host'] ?? null) ? strtolower($parsed['host']) : '';
        if (!in_array($scheme, ['http', 'https'], true) || '' === $host) {
            return null;
        }

        $reqUri = $request->getUri();
        $reqHost = strtolower($reqUri->getHost());
        if ('' === $reqHost) {
            $hostHeader = trim($request->getHeaderLine('Host'));
            $hostParts = '' !== $hostHeader ? parse_url('http://' . $hostHeader) : false;
            $reqHost = is_array($hostParts) && is_string($hostParts['host'] ?? null) ? strtolower($hostParts['host']) : '';
            $reqPort = is_array($hostParts) && is_int($hostParts['port'] ?? null) ? $hostParts['port'] : null;
        } else {
            $reqPort = $reqUri->getPort();
        }

        if ('' === $reqHost || $host !== $reqHost) {
            return null;
        }

        $refPort = is_int($parsed['port'] ?? null) ? $parsed['port'] : null;
        if ($refPort !== $reqPort) {
            return null;
        }

        $path = is_string($parsed['path'] ?? null) && '' !== $parsed['path'] ? $parsed['path'] : '/';
        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_starts_with($path, '/\\')) {
            return null;
        }
        $query = is_string($parsed['query'] ?? null) && '' !== $parsed['query'] ? '?' . $parsed['query'] : '';

        return $path . $query;
    }

    /**
     * @param array<string, string> $matchedParams
     */
    private function renderHttpError(
        Request $request,
        HttpException $e,
        ?int $startedNs = null,
        ?string $matchedRoute = null,
        ?string $matchedHandler = null,
        array $matchedParams = [],
    ): Response {
        $referenceId = ErrorReference::generate();
        $rawScrubbed = SecretScrubber::scrub($e->getMessage());
        $publicMessage = $this->debug
            ? $rawScrubbed
            : $this->vaguePublicHttpMessage($e->statusCode, $rawScrubbed);

        $explainer = new ErrorExplainer();
        $fullExplanation = $explainer->explainHttpStatus(
            statusCode: $e->statusCode,
            message: $rawScrubbed,
            method: $request->method(),
            path: $request->path(),
            availableRoutes: $this->debug ? $this->registeredRouteSignatures() : [],
        );

        // Log structured 5W1H incident server-side correlated by opaque Reference ID (for security/auth/server errors)
        $serverTrace = null;
        if ($e->statusCode >= 500 || in_array($e->statusCode, [401, 403, 405, 429], true)) {
            $this->logIncident($referenceId, $e->statusCode >= 500 ? 'error' : 'warning', $fullExplanation);
            if ($this->container->hasBinding(IncidentStoreInterface::class)) {
                $serverTrace = $this->buildRequestTrace($request, $referenceId, $startedNs, $matchedRoute, $matchedHandler, $matchedParams);
                $this->storeIncidentRecord($referenceId, $e->statusCode, $request, $fullExplanation, $serverTrace);
            }
        }

        $devExplanation = $this->debug ? $fullExplanation : null;
        $trace = $this->debug
            ? ($serverTrace ?? $this->buildRequestTrace($request, $referenceId, $startedNs, $matchedRoute, $matchedHandler, $matchedParams))
            : null;

        $headers = $e->headers;
        $headers['X-Kongono-Error-Id'] = $referenceId;
        if ($e->statusCode >= 500 || in_array($e->statusCode, [401, 403], true)) {
            $headers['Cache-Control'] ??= 'no-store';
        }

        if ($request->expectsJson()) {
            $payload = [
                'error' => 'http_error',
                'status' => $e->statusCode,
                'message' => $publicMessage,
                'reference' => $referenceId,
            ];
            if (null !== $devExplanation) {
                $payload['explanation'] = $devExplanation->toArray();
            }
            if (null !== $trace) {
                $payload['execution_trace'] = $trace->toArray();
            }

            return Response::json($payload, $e->statusCode, $headers);
        }

        $custom = $this->tryRenderCustomErrorView($e->statusCode, 'HTTP ' . $e->statusCode, $publicMessage, $request);
        if (null !== $custom) {
            return Response::html($custom, $e->statusCode, $headers);
        }

        return Response::html(
            (new ErrorPageRenderer())->render(
                statusCode: $e->statusCode,
                message: $publicMessage,
                request: $request,
                debug: $this->debug,
                appName: $this->name,
                explanation: $devExplanation,
                referenceId: $referenceId,
                trace: $trace,
            ),
            $e->statusCode,
            $headers,
        );
    }

    /**
     * @param array<string, string> $matchedParams
     */
    private function renderUnexpectedError(
        Request $request,
        Throwable $e,
        ?int $startedNs = null,
        ?string $matchedRoute = null,
        ?string $matchedHandler = null,
        array $matchedParams = [],
    ): Response {
        $referenceId = ErrorReference::generate();
        $routes = $this->debug ? $this->registeredRouteSignatures() : [];
        $explanation = (new ErrorExplainer())->explainThrowable(
            exception: $e,
            method: $request->method(),
            path: $request->path(),
            basePath: $this->basePath,
            availableRoutes: $routes,
        );

        // Always record full secret-scrubbed 5W1H diagnosis server-side indexed by Reference ID
        $this->logIncident($referenceId, 'error', $explanation);

        $serverTrace = null;
        if ($this->container->hasBinding(IncidentStoreInterface::class)) {
            $serverTrace = $this->buildRequestTrace($request, $referenceId, $startedNs, $matchedRoute, $matchedHandler, $matchedParams);
            $this->storeIncidentRecord($referenceId, 500, $request, $explanation, $serverTrace);
        }

        $trace = $this->debug
            ? ($serverTrace ?? $this->buildRequestTrace($request, $referenceId, $startedNs, $matchedRoute, $matchedHandler, $matchedParams))
            : null;

        $headers = [
            'X-Kongono-Error-Id' => $referenceId,
            'Cache-Control' => 'no-store',
        ];

        // Production mode is strictly vague & nondisclosing — zero internal details, diagnostics, or traces!
        $publicMessage = $this->debug
            ? SecretScrubber::scrub($e->getMessage())
            : 'An internal server error occurred.';

        $payload = [
            'error' => 'internal_server_error',
            'message' => $publicMessage,
            'reference' => $referenceId,
        ];

        if ($this->debug && $e instanceof CoreException && null !== $e->diagnostic) {
            $payload['diagnostic'] = $e->diagnostic->toSafeArray();
        }

        if ($this->debug && $request->expectsJson()) {
            $payload['explanation'] = $explanation->toArray();
            if (null !== $trace) {
                $payload['execution_trace'] = $trace->toArray();
            }
        }

        if ($request->expectsJson()) {
            return Response::json($payload, 500, $headers);
        }

        if ($this->debug) {
            return Response::html(
                (new DevErrorRenderer())->render(
                    $request,
                    $e,
                    $this->basePath,
                    $this->name,
                    availableRoutes: $routes,
                    trace: $trace,
                    explanation: $explanation,
                ),
                500,
                $headers,
            );
        }

        $custom = $this->tryRenderCustomErrorView(500, 'Internal Server Error', $publicMessage, $request, safeDiagnostic: null);
        if (null !== $custom) {
            return Response::html($custom, 500, $headers);
        }

        return Response::html(
            (new ErrorPageRenderer())->render(
                statusCode: 500,
                message: $publicMessage,
                request: $request,
                safeDiagnostic: null,
                debug: false,
                appName: $this->name,
                referenceId: $referenceId,
            ),
            500,
            $headers,
        );
    }

    /**
     * Neutralizes potentially revealing internal framework/security error wording in production (`APP_DEBUG=false`)
     * so potential attackers receive only vague, non-enumerable messages.
     */
    private function vaguePublicHttpMessage(int $statusCode, string $scrubbedMessage): string
    {
        $fallback = ErrorPageRenderer::statusMeta($statusCode)['default_message'];
        $trimmed = trim($scrubbedMessage);
        if ('' === $trimmed || strlen($trimmed) > 120) {
            return $fallback;
        }

        if (1 === preg_match('/\b(csrf|token|session|sqlstate|pdo|table|column|middleware|container|controller|exception|stack|route|method not allowed|rate limit|gate|policy|parameter|undefined|syntax|vendor|capability|feature|queue|allowlist|hmac|signature|webhook|principal|tenant|rls|guard|storage|bucket|redis|smtp|oauth|totp|passkey|argon2|bcrypt)\b|[\\\\\/]|\.php\b/i', $trimmed)) {
            return $fallback;
        }

        return $trimmed;
    }

    /**
     * @param array<string, string> $matchedParams
     */
    private function buildRequestTrace(
        Request $request,
        string $referenceId,
        ?int $startedNs,
        ?string $matchedRoute,
        ?string $matchedHandler,
        array $matchedParams,
    ): RequestTrace {
        $elapsedMs = null !== $startedNs ? max(0.0, (hrtime(true) - $startedNs) / 1_000_000.0) : 0.0;
        $middlewareShort = [];
        foreach ($this->middleware as $mw) {
            $fqcn = $mw::class;
            $pos = strrpos($fqcn, '\\');
            $middlewareShort[] = false === $pos ? $fqcn : substr($fqcn, $pos + 1);
        }

        $queryKeys = array_keys($request->getQueryParams());
        $parsedBody = $request->getParsedBody();
        $inputKeys = is_array($parsedBody) ? array_keys($parsedBody) : [];

        return new RequestTrace(
            referenceId: $referenceId,
            method: $request->method(),
            path: $request->path(),
            matchedRoute: $matchedRoute,
            matchedHandler: $matchedHandler,
            middleware: $middlewareShort,
            routeParams: $matchedParams,
            queryKeys: $queryKeys,
            inputKeys: $inputKeys,
            elapsedMs: $elapsedMs,
        );
    }

    private function logIncident(string $referenceId, string $level, ErrorExplanation $explanation): void
    {
        if (!$this->container->has(LoggerInterface::class)) {
            return;
        }

        try {
            $logger = $this->container->get(LoggerInterface::class);
            if (!$logger instanceof LoggerInterface) {
                return;
            }
            $exp = $explanation->toArray();
            $context = [
                'reference' => $referenceId,
                'code' => $exp['code'],
                'who' => $exp['who'],
                'what' => $exp['what'],
                'why' => $exp['why'],
                'where' => $exp['where'],
                'when' => $exp['when'],
                'how_to_fix' => $exp['how_to_fix'],
                'did_you_mean' => $exp['did_you_mean'],
            ];
            $msg = sprintf('[%s] %s — %s', $referenceId, $exp['code'], $exp['what']);
            if ('warning' === $level) {
                $logger->warning($msg, $context);
            } else {
                $logger->error($msg, $context);
            }
        } catch (Throwable) {
            // Never allow logging failures to cascade inside the error renderer
        }
    }

    private function storeIncidentRecord(
        string $referenceId,
        int $statusCode,
        Request $request,
        ErrorExplanation $explanation,
        RequestTrace $trace,
    ): void {
        try {
            $store = $this->container->get(IncidentStoreInterface::class);
            if (!$store instanceof IncidentStoreInterface) {
                return;
            }

            try {
                $rawBodyInput = $request->allInput();
            } catch (Throwable) {
                $rawBodyInput = [];
            }

            $record = new IncidentRecord(
                reference: $referenceId,
                occurredAt: gmdate('Y-m-d\TH:i:s\Z'),
                appName: $this->name,
                statusCode: $statusCode,
                method: $request->method(),
                path: SecretScrubber::scrub($request->path()),
                query: IncidentRecord::sanitizePayload($request->getQueryParams()),
                headers: IncidentRecord::sanitizeHeaders($request->getHeaders()),
                body: IncidentRecord::sanitizePayload($rawBodyInput),
                contentType: $request->getHeaderLine('Content-Type'),
                explanation: $explanation->toArray(),
                executionTrace: $trace->toArray(),
            );
            $store->record($record);
        } catch (Throwable) {
            // Never let incident recording break error responses
        }
    }

    /**
     * @internal Used by OpenApiGenerator to share identical DTO classification rules with App.
     * @param class-string $className
     */
    public function isInputDtoClass(string $className): bool
    {
        return !$this->container->hasBinding($className) && $this->looksLikeInputDtoCached($className);
    }

    /** @return list<string> */
    private function registeredRouteSignatures(): array
    {
        $out = [];
        foreach ($this->router->routes() as $route) {
            foreach ($route->methods as $m) {
                $out[] = strtoupper($m) . ' ' . $route->path;
            }
        }

        return $out;
    }

    /**
     * Render optional app-provided `views/errors/{status}.kgn.html` or `views/errors/error.kgn.html`.
     * Fails safely back to `ErrorPageRenderer` if a custom template is missing or throws.
     *
     * @param array<string, list<string>>                                                              $validationErrors
     * @param array{id: string, title: string, remediation: string, fields: array<string, mixed>}|null $safeDiagnostic
     */
    private function tryRenderCustomErrorView(
        int $statusCode,
        string $title,
        string $message,
        Request $request,
        array $validationErrors = [],
        ?array $safeDiagnostic = null,
    ): ?string {
        if (null === $this->views) {
            return null;
        }

        foreach (['errors/' . $statusCode, 'errors/error'] as $candidate) {
            if (!$this->views->exists($candidate)) {
                continue;
            }
            try {
                return $this->views->render($candidate, [
                    'status' => $statusCode,
                    'title' => $title,
                    'message' => $message,
                    'errors' => $validationErrors,
                    'diagnostic' => $safeDiagnostic,
                    'path' => SecretScrubber::scrub($request->path()),
                ]);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
