<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Internal\ContractGuard;

/** Carries lowercase SHA-256 content evidence without granting read authority. */
#[Api(ApiStatus::Experimental)]
final readonly class ContentIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::contentIdentity($value);
    }

    public static function fromBytes(string $bytes): self
    {
        ContractGuard::contentBytes($bytes);

        return new self(hash('sha256', $bytes));
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
