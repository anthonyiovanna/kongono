<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;

/** Carries the existing explicit trusted-presentation bootstrap permission. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedWebRuntimeBootstrap
{
    public function __construct(private TrustedDeveloperBootstrapPermission $permission) {}

    public function permission(): TrustedDeveloperBootstrapPermission
    {
        return $this->permission;
    }
}
