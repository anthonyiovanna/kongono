<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\InvalidAssetGraphException;

/** Carries lowercase SHA-256 evidence for one complete validated asset graph. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetGraphIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAssetGraphException::invalid('graph_relation');
        }
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
