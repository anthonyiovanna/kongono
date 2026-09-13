<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Value;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;
use Kongono\ViewContract\Schema\PresentationValueKind;

/** Carries exact bounded UTF-8 presentation text. */
#[Api(ApiStatus::Experimental)]
final readonly class StringValue implements PresentationValue
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::presentationText($value, 65536);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function kind(): PresentationValueKind
    {
        return PresentationValueKind::String;
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
