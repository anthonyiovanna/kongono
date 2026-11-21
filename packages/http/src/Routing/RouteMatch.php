<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

final readonly class RouteMatch
{
    /** @param array<string, string> $params */
    public function __construct(
        public Route $route,
        public array $params = [],
    ) {}
}
