<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Carries bounded normalized text without asserting truth or safety. */
#[Api(ApiStatus::Experimental)]
final readonly class ResultText
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::resultText($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
