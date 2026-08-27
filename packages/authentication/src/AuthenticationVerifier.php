<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Begins one explicit attempt for an exact registered mechanism. */
#[Api(ApiStatus::Experimental)]
interface AuthenticationVerifier
{
    public function mechanism(): AuthenticationMechanism;

    public function begin(
        AuthenticationEvidence $evidence,
        AuthenticationInstant $now,
    ): AuthenticationVerificationOperation;
}
