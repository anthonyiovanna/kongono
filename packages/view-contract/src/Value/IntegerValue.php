<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Value;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Schema\PresentationValueKind;

/** Carries one exact native integer. */
#[Api(ApiStatus::Experimental)]
final readonly class IntegerValue implements PresentationValue
{
    public function __construct(private int $value) {}

    public function value(): int
    {
        return $this->value;
    }

    public function kind(): PresentationValueKind
    {
        return PresentationValueKind::Integer;
    }

    public function equals(PresentationValue $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function depth(): int
    {
        return 1;
    }

    public function nodeCount(): int
    {
        return 1;
    }
}
