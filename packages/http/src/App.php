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
use ReflectionParameter;
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

        return $this;
    }

    /**
     * @param class-string|string                 $id
     * @param (Closure(Container): mixed)|class-string $concrete
     */
    public function singleton(string $id, Closure|string $concrete): self
    {
        $this->container->singleton($id, $concrete);

        return $this;
    }

    /**
     * @param class-string|string                 $id
     * @param (Closure(Container): mixed)|class-string $concrete
     */
    public function scoped(string $id, Closure|string $concrete): self
    {
        $this->container->scoped($id, $concrete);

        return $this;
    }

    public function instance(string $id, mixed $value): self
    {
        if (ViewEngine::class === $id && $value instanceof ViewEngine) {
            $this->views = $value;
        }
        $this->container->instance($id, $value);

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
                        $req = $request instanceof Request ? $request : $this->wrapRequest($request);

                        return $this->app->dispatchSafely($req);
                    }

                    private function wrapRequest(ServerRequestInterface $psr): Request
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
            $response = Response::json(['error' => ['status' => $e->statusCode, 'message' => $e->getMessage()]], $e->statusCode, $e->headers);
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
                    $response = Response::json(['error' => ['status' => $e->statusCode, 'message' => $e->getMessage()]], $e->statusCode, $e->headers);
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
    public function dispatch(Request $request): ResponseInterface
    {
        $match = $this->router->match($request->method(), $request->path());
        $enriched = $request->withAttribute('_route_params', $match->params);

        $routeTerminal = new readonly class ($this, $match) implements RequestHandlerInterface {
            public function __construct(
                private App $app,
                private RouteMatch $match,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->app->invokeRouteHandler(
                    $this->match,
                    $request instanceof Request ? $request : Request::create($request->getMethod(), (string) $request->getUri()),
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
            $reflection = new ReflectionFunction($handler);
            $args = $this->resolveHandlerParameters($reflection, $request, $match->params);
            $result = $handler(...$args);
        } else {
            [$controllerClass, $methodName] = $handler;
            $controller = $this->container->get($controllerClass);
            if (!is_object($controller)) {
                throw new CoreException(sprintf('Controller "%s" did not resolve to an object.', $controllerClass));
            }
            $reflection = new ReflectionMethod($controller, $methodName);
            $args = $this->resolveHandlerParameters($reflection, $request, $match->params);
            $result = $reflection->invokeArgs($controller, $args);
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
     * @param array<string, string> $routeParams
     * @return list<mixed>
     */
    private function resolveHandlerParameters(ReflectionFunctionAbstract $reflection, Request $request, array $routeParams): array
    {
        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $args[] = $this->resolveHandlerParameter($parameter, $request, $routeParams);
        }

        return $args;
    }

    /** @param array<string, string> $routeParams */
    private function resolveHandlerParameter(ReflectionParameter $parameter, Request $request, array $routeParams): mixed
    {
        $name = $parameter->getName();
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            if (Request::class === $typeName || ServerRequestInterface::class === $typeName) {
                return $request;
            }

            // Inject request attributes by exact type (e.g. Session, Principal)
            $attrValue = $request->getAttribute($typeName);
            if (is_object($attrValue) && $attrValue instanceof $typeName) {
                return $attrValue;
            }

            if ($type->isBuiltin()) {
                if (array_key_exists($name, $routeParams)) {
                    return $this->coerceRouteParam($routeParams[$name], $typeName, $name);
                }
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                throw new HttpException(400, sprintf('Missing required parameter "%s".', $name));
            }

            // Explicit container binding (or framework service like ViewEngine/Config/Repository)
            if (array_key_exists($typeName, $this->container->registeredBindings())) {
                return $this->container->get($typeName);
            }

            // Check if this is an input DTO class (concrete userland class with scalar/validated constructor params)
            // Authoritative route parameters ALWAYS take precedence over query/body input (prevents route-param shadowing IDOR)
            if (class_exists($typeName) && $this->looksLikeInputDto($typeName)) {
                return $this->validator->validate($typeName, [...$request->allInput(), ...$routeParams]);
            }

            // Fall back to container autowiring for services/repositories
            if ($this->container->has($typeName)) {
                return $this->container->get($typeName);
            }
        }

        if (array_key_exists($name, $routeParams)) {
            return $routeParams[$name];
        }
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new CoreException(sprintf('Unable to resolve route parameter "$%s".', $name));
    }

    /** @param class-string $className */
    private function looksLikeInputDto(string $className): bool
    {
        $ref = new \ReflectionClass($className);
        if (!$ref->isInstantiable() || $ref->isInternal()) {
            return false;
        }
        $ctor = $ref->getConstructor();
        if (null === $ctor || 0 === $ctor->getNumberOfParameters()) {
            return false;
        }

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
                if (enum_exists($paramTypeName)) {
                    $isDtoFieldType = true;
                } elseif (
                    class_exists($paramTypeName)
                    && !array_key_exists($paramTypeName, $this->container->registeredBindings())
                    && $this->looksLikeInputDto($paramTypeName)
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
