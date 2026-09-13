<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Value;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Schema\PresentationValueKind;

/** Carries the exact null presentation value. */
#[Api(ApiStatus::Experimental)]
final readonly class NullValue implements PresentationValue
{
    public function kind(): PresentationValueKind
    {
        return PresentationValueKind::Null;
    }

    public function equals(PresentationValue $other): bool
    {
        return $other instanceof self;
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
