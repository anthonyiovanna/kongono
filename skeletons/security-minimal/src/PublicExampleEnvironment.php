<?php

declare(strict_types=1);

namespace KongonoSecurityMinimal;

use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;

/** Supplies one fixed application-owned environment profile for the public example. */
final readonly class PublicExampleEnvironment implements AuthorizationEnvironmentEvidence
{
    public function profile(): AuthorizationEnvironmentProfile
    {
        return new AuthorizationEnvironmentProfile('public.example.v1');
    }
}
