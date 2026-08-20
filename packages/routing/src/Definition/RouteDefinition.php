<?php

declare(strict_types=1);

namespace Kongono\Routing\Definition;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Routing\Internal\RouteSyntax;

/** Immutable declaration of one named route and its exact matching inputs. */
#[Api(ApiStatus::Experimental)]
final readonly class RouteDefinition
{
    private string $name;

    /** @var list<string> */
    private array $methods;

    private string $pathTemplate;

    /** @param array<array-key, mixed> $methods */
    public function __construct(string $name, array $methods, string $pathTemplate)
    {
        $this->name = RouteSyntax::routeName($name);
        $this->methods = RouteSyntax::routeMethods($methods);
        RouteSyntax::routeTemplate($pathTemplate);
        $this->pathTemplate = $pathTemplate;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return list<string> */
    public function methods(): array
    {
        return $this->methods;
    }

    public function pathTemplate(): string
    {
        return $this->pathTemplate;
    }
}
