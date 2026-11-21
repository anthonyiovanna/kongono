<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class In
{
    /** @param list<string|int> $allowed */
    public function __construct(
        public array $allowed,
        public ?string $message = null,
    ) {}
}
