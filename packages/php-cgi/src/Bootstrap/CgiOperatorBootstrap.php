<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Bootstrap;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\PhpCgi\Internal\CgiOperatorBootstrapValidation;

/** Retains one validated immutable operator permission without process evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CgiOperatorBootstrap
{
    private function __construct(
        private TrustedDeveloperBootstrapPermission $bootstrapPermission,
    ) {}

    public static function load(CgiOperatorProfile $profile): self
    {
        CgiOperatorBootstrapValidation::captureAndValidate();

        return new self($profile->bootstrapPermission());
    }

    public function bootstrapPermission(): TrustedDeveloperBootstrapPermission
    {
        return $this->bootstrapPermission;
    }
}
