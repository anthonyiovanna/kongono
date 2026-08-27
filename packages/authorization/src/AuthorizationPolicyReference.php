<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Binds one policy name, version, and provenance into an exact identity. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthorizationPolicyReference
{
    public function __construct(
        private AuthorizationPolicyName $name,
        private AuthorizationPolicyVersion $version,
        private AuthorizationPolicyProvenance $provenance,
    ) {}

    public function name(): AuthorizationPolicyName
    {
        return $this->name;
    }

    public function version(): AuthorizationPolicyVersion
    {
        return $this->version;
    }

    public function provenance(): AuthorizationPolicyProvenance
    {
        return $this->provenance;
    }

    public function equals(self $other): bool
    {
        return $this->name->equals($other->name)
            && $this->version->equals($other->version)
            && $this->provenance->equals($other->provenance);
    }
}
