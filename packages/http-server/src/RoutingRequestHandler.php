<?php

declare(strict_types=1);

namespace Kongono\Http\Server;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Match\RouteMatch;
use Kongono\Routing\Matcher\RouteMatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Maps a fresh deterministic route match to one explicitly supplied PSR-15 handler. */
#[Api(ApiStatus::Experimental)]
final readonly class RoutingRequestHandler implements RequestHandlerInterface
{
    public const string ROUTE_MATCH_ATTRIBUTE = RouteMatch::class;

    private RouteTable $routes;
    private RouteMatcher $matcher;

    /** @var array<string, RequestHandlerInterface> */
    private array $handlers;

    private RequestHandlerInterface $unmatchedHandler;

    /** @param array<array-key, mixed> $handlers */
    public function __construct(RouteTable $routes, array $handlers, RequestHandlerInterface $unmatchedHandler)
    {
        $this->routes = $routes;
        $this->matcher = new RouteMatcher($routes);
        $this->unmatchedHandler = $unmatchedHandler;

        $routeNames = array_map(static fn(RouteDefinition $route): string => $route->name(), $routes->routes());
        $expected = array_fill_keys($routeNames, true);
        $missingCount = 0;
        foreach ($routeNames as $routeName) {
            if (!array_key_exists($routeName, $handlers)) {
                ++$missingCount;
            }
        }
        $unexpectedCount = 0;
        foreach (array_keys($handlers) as $handlerKey) {
            if (!is_string($handlerKey) || !array_key_exists($handlerKey, $expected)) {
                ++$unexpectedCount;
            }
        }

        if (0 !== $missingCount || 0 !== $unexpectedCount) {
            throw new InvalidHttpServerDefinitionException(HttpServerDiagnostic::InvalidRouteHandlerKeys, [
                'route_count' => count($routeNames),
                'handler_count' => count($handlers),
                'missing_count' => $missingCount,
                'unexpected_count' => $unexpectedCount,
            ]);
        }

        $canonicalHandlers = [];
        foreach ($routeNames as $position => $routeName) {
            $handler = $handlers[$routeName];
            if (!$handler instanceof RequestHandlerInterface) {
                throw new InvalidHttpServerDefinitionException(HttpServerDiagnostic::InvalidRouteHandlerValue, [
                    'handler_position' => $position,
                    'violation' => 'not_request_handler',
                ]);
            }
            $canonicalHandlers[$routeName] = $handler;
        }
        $this->handlers = $canonicalHandlers;
    }

    public function routes(): RouteTable
    {
        return $this->routes;
    }

    /** @return array<string, RequestHandlerInterface> */
    public function handlers(): array
    {
        return $this->handlers;
    }

    public function unmatchedHandler(): RequestHandlerInterface
    {
        return $this->unmatchedHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $match = $this->matcher->match($request);
        if (null === $match) {
            return $this->unmatchedHandler->handle($request);
        }

        $handler = $this->handlers[$match->route()->name()];

        return $handler->handle($request->withAttribute(self::ROUTE_MATCH_ATTRIBUTE, $match));
    }
}
