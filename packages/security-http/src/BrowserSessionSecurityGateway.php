<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationOrchestrator;
use Kongono\Authentication\AuthenticationRequest;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\SecurityHttp\Exception\BrowserSessionAuthenticationException;
use Kongono\SecurityHttp\Internal\BrowserSessionRequestExtractor;
use Kongono\SecurityRuntime\GeneratedSecurityApplication;
use Psr\Http\Message\ServerRequestInterface;

/** Enters one anonymous or exactly authenticated generated security operation. */
#[Api(ApiStatus::Experimental)]
final readonly class BrowserSessionSecurityGateway
{
    private BrowserSessionRequestExtractor $extractor;

    public function __construct(
        TrustedOrigin $trustedOrigin,
        private AuthenticationOrchestrator $authentication,
        private AuthenticationRequirement $requirement,
        private GeneratedSecurityApplication $application,
    ) {
        $this->extractor = new BrowserSessionRequestExtractor($trustedOrigin);
    }

    public function begin(
        ServerRequestInterface $request,
        AuthenticationInstant $now,
    ): BrowserSessionSecurityOperation {
        $profile = $this->extractor->extract($request);
        $sessionIdentifier = $profile->sessionIdentifier();
        if (null === $sessionIdentifier) {
            return new BrowserSessionSecurityOperation($this->application->beginAnonymousOperation());
        }

        $evidence = new BrowserSessionEvidence(
            $sessionIdentifier,
            $profile->requiresCsrfProof(),
            $profile->csrfProof(),
        );
        $verification = $this->authentication->authenticate(new AuthenticationRequest(
            [$evidence],
            $now,
            $this->requirement,
        ));
        if (AuthenticationOutcomeKind::Authenticated !== $verification->outcome()->kind()) {
            throw BrowserSessionAuthenticationException::rejected();
        }

        return new BrowserSessionSecurityOperation(
            $this->application->beginAuthenticatedOperation($verification),
        );
    }
}
