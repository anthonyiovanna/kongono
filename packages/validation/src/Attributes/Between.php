<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;

/** Validates that a number or string length falls inclusively between $min and $max. */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Between
{
    public function __construct(
        public int|float $min,
        public int|float $max,
        public ?string $message = null,
    ) {}
}
