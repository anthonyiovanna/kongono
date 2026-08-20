<?php

declare(strict_types=1);

namespace Kongono\Routing\Definition;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\InvalidRouteDefinitionException;
use Kongono\Routing\Internal\RouteSyntax;

/** Immutable, canonically ordered collection of conflict-free route declarations. */
#[Api(ApiStatus::Experimental)]
final readonly class RouteTable
{
    /** @var list<RouteDefinition> */
    private array $routes;

    /** @param array<array-key, mixed> $routes */
    public function __construct(array $routes)
    {
        if (!array_is_list($routes)) {
            throw new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteTable, [
                'entry_count' => count($routes),
                'violation' => 'not_list',
            ]);
        }
        if (count($routes) > 1024) {
            throw new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteTable, [
                'entry_count' => count($routes),
                'violation' => 'too_many',
            ]);
        }

        $validated = [];
        $namePositions = [];
        $patternPositions = [];
        foreach ($routes as $position => $route) {
            if (!$route instanceof RouteDefinition) {
                throw new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteTable, [
                    'entry_count' => count($routes),
                    'violation' => 'not_route',
                ]);
            }

            if (array_key_exists($route->name(), $namePositions)) {
                throw new InvalidRouteDefinitionException(RoutingDiagnostic::DuplicateRouteName, [
                    'first_route_position' => $namePositions[$route->name()],
                    'duplicate_route_position' => $position,
                ]);
            }
            $namePositions[$route->name()] = $position;

            $syntax = RouteSyntax::routeTemplate($route->pathTemplate());
            foreach ($route->methods() as $method) {
                $patternKey = $method . "\0" . $syntax['structural_pattern'];
                if (array_key_exists($patternKey, $patternPositions)) {
                    throw new InvalidRouteDefinitionException(RoutingDiagnostic::ConflictingRoutePattern, [
                        'first_route_position' => $patternPositions[$patternKey],
                        'conflicting_route_position' => $position,
                    ]);
                }
                $patternPositions[$patternKey] = $position;
            }
            $validated[] = $route;
        }

        usort($validated, static fn(RouteDefinition $left, RouteDefinition $right): int => strcmp($left->name(), $right->name()));
        $this->routes = $validated;
    }

    /** @return list<RouteDefinition> */
    public function routes(): array
    {
        return $this->routes;
    }
}
