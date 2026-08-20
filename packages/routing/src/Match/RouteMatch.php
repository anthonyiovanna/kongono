<?php

declare(strict_types=1);

namespace Kongono\Routing\Match;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Internal\RouteSyntax;

/** Immutable result that retains only the selected route and validated encoded parameters. */
#[Api(ApiStatus::Experimental)]
final readonly class RouteMatch
{
    private RouteDefinition $route;

    /** @var array<string, string> */
    private array $parameters;

    /** @param array<array-key, mixed> $parameters */
    public function __construct(RouteDefinition $route, array $parameters)
    {
        $this->route = $route;
        $this->parameters = RouteSyntax::routeMatchParameters($route->pathTemplate(), $parameters);
    }

    public function route(): RouteDefinition
    {
        return $this->route;
    }

    /** @return array<string, string> */
    public function parameters(): array
    {
        return $this->parameters;
    }
}
