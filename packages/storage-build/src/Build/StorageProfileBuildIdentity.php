<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\InvalidStorageProfileArtifactException;
use Kongono\StorageBuild\Internal\BuildGuard;
use Kongono\StorageBuild\Internal\CanonicalJson;
use Kongono\StorageBuild\Profile\StorageProfileSetIdentity;

/** Carries the deterministic identity of one fixed-profile build input. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileBuildIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidStorageProfileArtifactException::invalid('build_identity');
        }
    }

    public static function fromSourceIdentity(StorageProfileSetIdentity $sourceIdentity): self
    {
        $descriptor = CanonicalJson::encode([
            'schema' => BuildGuard::BUILD_PROFILE,
            'source_identity' => $sourceIdentity->value(),
        ]);

        return new self(hash('sha256', BuildGuard::BUILD_DOMAIN . "\0" . $descriptor));
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
