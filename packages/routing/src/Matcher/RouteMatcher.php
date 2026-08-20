<?php

declare(strict_types=1);

namespace Kongono\Routing\Matcher;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Internal\RouteSyntax;
use Kongono\Routing\Match\RouteMatch;
use Psr\Http\Message\ServerRequestInterface;

/** Stateless deterministic matcher over an immutable route-table snapshot. */
#[Api(ApiStatus::Experimental)]
final readonly class RouteMatcher implements RouteMatcherInterface
{
    private RouteTable $routes;

    /** @var list<array{route: RouteDefinition, methods: list<string>, segments: list<array{kind: 'literal'|'parameter', value: string}>, specificity: string}> */
    private array $compiledRoutes;

    public function __construct(RouteTable $routes)
    {
        $this->routes = $routes;
        $compiled = [];
        foreach ($this->routes->routes() as $route) {
            $syntax = RouteSyntax::routeTemplate($route->pathTemplate());
            $compiled[] = [
                'route' => $route,
                'methods' => $route->methods(),
                'segments' => $syntax['segments'],
                'specificity' => $syntax['specificity'],
            ];
        }
        $this->compiledRoutes = $compiled;
    }

    public function match(ServerRequestInterface $request): ?RouteMatch
    {
        $method = RouteSyntax::requestMethod($request->getMethod());
        $path = $request->getUri()->getPath();
        $requestSegments = RouteSyntax::requestPath($path);
        $best = null;
        $bestParameters = [];
        $bestSpecificity = null;

        foreach ($this->compiledRoutes as $candidate) {
            if (!in_array($method, $candidate['methods'], true)
                || count($requestSegments) !== count($candidate['segments'])) {
                continue;
            }

            $parameters = [];
            foreach ($candidate['segments'] as $position => $routeSegment) {
                if ('parameter' === $routeSegment['kind']) {
                    if ('' === $requestSegments[$position]) {
                        continue 2;
                    }
                    $parameters[$routeSegment['value']] = $requestSegments[$position];
                    continue;
                }
                if ($routeSegment['value'] !== $requestSegments[$position]) {
                    continue 2;
                }
            }

            if (null === $bestSpecificity || strcmp($candidate['specificity'], $bestSpecificity) > 0) {
                $best = $candidate['route'];
                $bestParameters = $parameters;
                $bestSpecificity = $candidate['specificity'];
            }
        }

        return null === $best ? null : new RouteMatch($best, $bestParameters);
    }
}
