<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;

/** Declares a shared URL path prefix for every attribute route on a controller class. */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Prefix
{
    public function __construct(
        public string $path,
    ) {}
}
