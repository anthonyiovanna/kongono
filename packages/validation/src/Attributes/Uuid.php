<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;

/** Validates that a string is a valid canonical UUID (RFC 4122 / 9562). */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Uuid
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
