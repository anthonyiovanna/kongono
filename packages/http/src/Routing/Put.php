<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Put extends RouteAttribute
{
    public function __construct(string $path, ?string $name = null)
    {
        parent::__construct(['PUT'], $path, $name);
    }
}
