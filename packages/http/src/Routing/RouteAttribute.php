<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RouteAttribute
{
    /** @param list<string> $methods */
    public function __construct(
        public readonly array $methods,
        public readonly string $path,
        public readonly ?string $name = null,
    ) {}
}
