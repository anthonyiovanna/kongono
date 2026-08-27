<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityHttp\BrowserSessionCsrfProof;
use Kongono\SecurityHttp\BrowserSessionIdentifier;

/** Carries one detached validated request projection without retaining a request. */
#[Api(ApiStatus::Internal)]
final readonly class BrowserSessionRequestProfile
{
    public function __construct(
        private ?BrowserSessionIdentifier $sessionIdentifier,
        private ?BrowserSessionCsrfProof $csrfProof,
    ) {}

    public static function anonymous(): self
    {
        return new self(null, null);
    }

    public function sessionIdentifier(): ?BrowserSessionIdentifier
    {
        return $this->sessionIdentifier;
    }

    public function csrfProof(): ?BrowserSessionCsrfProof
    {
        return $this->csrfProof;
    }

    public function requiresCsrfProof(): bool
    {
        return null !== $this->csrfProof;
    }
}
