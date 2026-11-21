<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;

/** Validates that this field equals another input field (e.g. `#[Confirmed('password')]`). */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Confirmed
{
    public function __construct(
        public string $otherField = 'password',
        public ?string $message = null,
    ) {}
}
