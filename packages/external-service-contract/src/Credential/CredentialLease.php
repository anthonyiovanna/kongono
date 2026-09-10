<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Credential;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines explicit borrowed credential access and retirement without an implementation. */
#[Api(ApiStatus::Experimental)]
interface CredentialLease
{
    public function identity(): CredentialLeaseIdentity;

    public function reference(): CredentialReference;

    public function purpose(): CredentialPurpose;

    public function state(): CredentialLeaseState;

    public function withMaterial(CredentialMaterialConsumer $consumer): void;

    public function retire(): void;
}
