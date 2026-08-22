<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Bootstrap;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;

/** Selects the operator-owned trusted-presentation decision for one immutable bootstrap. */
#[Api(ApiStatus::Experimental)]
enum CgiOperatorProfile
{
    case Production;
    case LoopbackDevelopment;

    public function bootstrapPermission(): TrustedDeveloperBootstrapPermission
    {
        return match ($this) {
            self::Production => TrustedDeveloperBootstrapPermission::Denied,
            self::LoopbackDevelopment => TrustedDeveloperBootstrapPermission::Allowed,
        };
    }
}
