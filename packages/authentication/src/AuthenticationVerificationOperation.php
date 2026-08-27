<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns one verifier attempt and its explicit cleanup lifecycle. */
#[Api(ApiStatus::Experimental)]
interface AuthenticationVerificationOperation
{
    public function verify(): AuthenticationVerification;

    public function retire(): void;
}
