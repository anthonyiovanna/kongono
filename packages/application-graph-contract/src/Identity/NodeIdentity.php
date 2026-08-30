<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Internal\IdentityGuard;

/** Holds one stable opaque node identity derived from a canonical SHA-256 digest. */
#[Api(ApiStatus::Experimental)]
final readonly class NodeIdentity
{
    private string $sha256;

    public function __construct(string $sha256)
    {
        $this->sha256 = IdentityGuard::identityDigest($sha256);
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    public function value(): string
    {
        return 'kgn:graph:v1:node:' . $this->sha256;
    }
}
