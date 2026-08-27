<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Tests\Fixture;

use Closure;
use Kongono\Authentication\AuthenticationEvidence;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authentication\AuthenticationVerificationOperation;
use Kongono\Authentication\AuthenticationVerifier;
use Kongono\SecurityHttp\BrowserSessionEvidence;
use Kongono\SecurityHttp\BrowserSessionProfile;
use LogicException;
use Throwable;

final class TestBrowserSessionVerifier implements AuthenticationVerifier
{
    public int $beginCalls = 0;
    public int $verifyCalls = 0;
    public int $retireCalls = 0;
    public ?BrowserSessionEvidence $lastEvidence = null;
    public ?Closure $onVerify = null;
    public ?Throwable $verifyFailure = null;
    public ?Throwable $retireFailure = null;

    public function __construct(public AuthenticationVerification $verification) {}

    public function mechanism(): AuthenticationMechanism
    {
        return new AuthenticationMechanism(BrowserSessionProfile::NAME);
    }

    public function begin(AuthenticationEvidence $evidence, AuthenticationInstant $now): AuthenticationVerificationOperation
    {
        if (!$evidence instanceof BrowserSessionEvidence) {
            throw new LogicException('unexpected evidence');
        }
        ++$this->beginCalls;
        $this->lastEvidence = $evidence;

        return new class ($this) implements AuthenticationVerificationOperation {
            private bool $retired = false;

            public function __construct(private readonly TestBrowserSessionVerifier $verifier) {}

            public function verify(): AuthenticationVerification
            {
                ++$this->verifier->verifyCalls;
                $callback = $this->verifier->onVerify;
                if (null !== $callback) {
                    $callback();
                }
                if (null !== $this->verifier->verifyFailure) {
                    throw $this->verifier->verifyFailure;
                }

                return $this->verifier->verification;
            }

            public function retire(): void
            {
                if (!$this->retired) {
                    $this->retired = true;
                    ++$this->verifier->retireCalls;
                    if (null !== $this->verifier->retireFailure) {
                        throw $this->verifier->retireFailure;
                    }
                }
            }
        };
    }
}
