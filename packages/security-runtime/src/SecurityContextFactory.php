<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\SecurityRuntime\Exception\InvalidSecurityContextVerificationException;

/** Creates fresh operation contexts from explicit anonymous intent or exact authentication evidence. */
#[Api(ApiStatus::Experimental)]
final class SecurityContextFactory
{
    public function anonymous(): SecurityContext
    {
        return SecurityContext::anonymous();
    }

    public function authenticated(AuthenticationVerification $verification): SecurityContext
    {
        $outcome = $verification->outcome();
        $facts = $verification->facts();
        if (AuthenticationOutcomeKind::Authenticated !== $outcome->kind()) {
            throw InvalidSecurityContextVerificationException::outcome();
        }
        if (null === $facts || null === $outcome->principal()) {
            throw InvalidSecurityContextVerificationException::facts();
        }
        if ($facts->principal() !== $outcome->principal()) {
            throw InvalidSecurityContextVerificationException::principalIdentity();
        }

        return SecurityContext::authenticated($facts->principal());
    }
}
