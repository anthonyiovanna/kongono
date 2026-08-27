<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\AuthorizationPolicyReference;

/** Carries the detached exact rule-free policy identity model. */
#[Api(ApiStatus::Experimental)]
final readonly class SecurityPolicyModel
{
    public function __construct(private AuthorizationPolicyReference $reference) {}

    public function reference(): AuthorizationPolicyReference
    {
        return $this->reference;
    }
}
