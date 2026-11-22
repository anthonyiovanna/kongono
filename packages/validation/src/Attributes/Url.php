<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Url
{
    /** @param list<string> $allowedSchemes Explicit URL schemes admitted (default http/https; blocks javascript:, file://, data:, gopher://) */
    public function __construct(
        public ?string $message = null,
        public array $allowedSchemes = ['http', 'https'],
    ) {}
}
