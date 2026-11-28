<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;

/**
 * Constrains a controller class or route method to a specific host or parameterized domain template
 * (e.g. `#[Domain('bbl.to')]` for branded short links or `#[Domain('{tenant}.bidbylink.com')]` for multi-tenancy).
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class Domain
{
    public function __construct(
        public string $host,
    ) {}
}
