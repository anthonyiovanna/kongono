<?php

declare(strict_types=1);

namespace KongonoSecurityMinimal;

use Kongono\Authentication\AuthenticationEvidence;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authentication\AuthenticationVerificationOperation;
use Kongono\Authentication\AuthenticationVerifier;
use Kongono\SecurityHttp\BrowserSessionProfile;

/** Explicitly rejects every presented browser-session evidence item. */
final readonly class NoSessionVerifier implements AuthenticationVerifier
{
    public function mechanism(): AuthenticationMechanism
    {
        return new AuthenticationMechanism(BrowserSessionProfile::NAME);
    }

    public function begin(AuthenticationEvidence $evidence, AuthenticationInstant $now): AuthenticationVerificationOperation
    {
        return new class implements AuthenticationVerificationOperation {
            public function verify(): AuthenticationVerification
            {
                return AuthenticationVerification::invalid();
            }

            public function retire(): void {}
        };
    }
}
