<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement\Tests\Fixture;

use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\AuthorizationPolicyReference;

final readonly class TestAuthorizationPolicyEvidence implements AuthorizationPolicyEvidence
{
    public function __construct(private AuthorizationPolicyReference $reference) {}

    public function reference(): AuthorizationPolicyReference
    {
        return $this->reference;
    }
}
