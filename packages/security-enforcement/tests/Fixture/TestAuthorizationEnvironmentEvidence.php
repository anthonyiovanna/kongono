<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement\Tests\Fixture;

use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;

final readonly class TestAuthorizationEnvironmentEvidence implements AuthorizationEnvironmentEvidence
{
    public function __construct(private AuthorizationEnvironmentProfile $profile) {}

    public function profile(): AuthorizationEnvironmentProfile
    {
        return $this->profile;
    }
}
