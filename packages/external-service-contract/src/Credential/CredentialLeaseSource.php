<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Credential;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines explicit lease acquisition without selecting or implementing a source. */
#[Api(ApiStatus::Experimental)]
interface CredentialLeaseSource
{
    public function acquire(CredentialLeaseRequest $request): CredentialLease;
}
