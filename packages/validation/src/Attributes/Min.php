<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Min
{
    public function __construct(
        public int|float $value,
        public ?string $message = null,
    ) {}
}
