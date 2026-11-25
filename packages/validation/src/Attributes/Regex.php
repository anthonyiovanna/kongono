<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Regex
{
    public function __construct(
        public string $pattern,
        public ?string $message = null,
    ) {
        if ('' === $pattern || false === @preg_match($pattern, '')) {
            throw new InvalidArgumentException(sprintf('Invalid regular expression pattern "%s" in #[Regex].', $pattern));
        }
    }
}
