<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;
use Kongono\IdentityAccess\PrincipalReference;

/** Carries bounded validated authentication facts without granting permission. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthenticationFacts
{
    public function __construct(
        private PrincipalReference $principal,
        private AuthenticationMechanism $mechanism,
        private AuthenticationStrength $strength,
        private AuthenticationInstant $authenticatedAt,
        private AuthenticationInstant $validUntil,
    ) {
        if ($authenticatedAt->isAfter($validUntil)) {
            throw InvalidAuthenticationValueException::verification();
        }
    }

    public function principal(): PrincipalReference
    {
        return $this->principal;
    }

    public function mechanism(): AuthenticationMechanism
    {
        return $this->mechanism;
    }

    public function strength(): AuthenticationStrength
    {
        return $this->strength;
    }

    public function authenticatedAt(): AuthenticationInstant
    {
        return $this->authenticatedAt;
    }

    public function validUntil(): AuthenticationInstant
    {
        return $this->validUntil;
    }
}
