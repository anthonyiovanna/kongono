<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\AuthenticationEvidence;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\SecurityHttp\Exception\InvalidBrowserSessionValueException;
use SensitiveParameter;

/** Supplies detached opaque browser-session evidence to one selected verifier. */
#[Api(ApiStatus::Experimental)]
final readonly class BrowserSessionEvidence implements AuthenticationEvidence
{
    public function __construct(
        #[SensitiveParameter]
        private BrowserSessionIdentifier $sessionIdentifier,
        private bool $requiresCsrfProof,
        #[SensitiveParameter]
        private ?BrowserSessionCsrfProof $csrfProof,
    ) {
        if ($requiresCsrfProof !== (null !== $csrfProof)) {
            throw InvalidBrowserSessionValueException::evidenceShape();
        }
    }

    public function mechanism(): AuthenticationMechanism
    {
        return new AuthenticationMechanism(BrowserSessionProfile::NAME);
    }

    /** Exposes the exact sensitive identifier only to the selected verifier. */
    public function sessionIdentifier(): BrowserSessionIdentifier
    {
        return $this->sessionIdentifier;
    }

    public function requiresCsrfProof(): bool
    {
        return $this->requiresCsrfProof;
    }

    /** Exposes the exact optional sensitive proof only to the selected verifier. */
    public function csrfProof(): ?BrowserSessionCsrfProof
    {
        return $this->csrfProof;
    }
}
