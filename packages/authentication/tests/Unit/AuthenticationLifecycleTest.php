<?php

declare(strict_types=1);

namespace Kongono\Authentication\Tests\Unit;

use Kongono\Authentication\AuthenticationEvidence;
use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationOrchestrator;
use Kongono\Authentication\AuthenticationRequest;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authentication\AuthenticationVerificationOperation;
use Kongono\Authentication\AuthenticationVerifier;
use Kongono\Authentication\Exception\AuthenticationCleanupException;
use Kongono\Authentication\Exception\AuthenticationOperationAndCleanupException;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class AuthenticationLifecycleTest extends TestCase
{
    public function testBeginFailurePropagatesExactlyAndCreatesNoOperation(): void
    {
        $failure = new RuntimeException('synthetic begin failure');
        $verifier = new LifecycleVerifier($this->mechanism(), $failure);

        try {
            (new AuthenticationOrchestrator([$verifier]))->authenticate($this->request());
            self::fail('Begin failure was not propagated.');
        } catch (Throwable $caught) {
            self::assertSame($failure, $caught);
            self::assertSame(1, $verifier->beginCalls);
            self::assertSame([], $verifier->operations);
        }
    }

    public function testVerifyFailureRetiresOnceAndRethrowsExactPrimary(): void
    {
        $primary = new RuntimeException('synthetic operation failure');
        $operation = new LifecycleOperation(null, $primary, null);
        $verifier = new LifecycleVerifier($this->mechanism(), null, $operation);

        try {
            (new AuthenticationOrchestrator([$verifier]))->authenticate($this->request());
            self::fail('Operation failure was not propagated.');
        } catch (Throwable $caught) {
            self::assertSame($primary, $caught);
            self::assertSame(1, $operation->verifyCalls);
            self::assertSame(1, $operation->retireCalls);
        }
    }

    public function testCleanupFailureAfterResultPreservesExactCleanupCause(): void
    {
        $cleanup = new RuntimeException('synthetic cleanup failure');
        $operation = new LifecycleOperation(AuthenticationVerification::invalid(), null, $cleanup);

        try {
            (new AuthenticationOrchestrator([new LifecycleVerifier($this->mechanism(), null, $operation)]))
                ->authenticate($this->request());
            self::fail('Cleanup failure was not composed.');
        } catch (AuthenticationCleanupException $failure) {
            self::assertSame($cleanup, $failure->cleanupFailure());
            self::assertSame($cleanup, $failure->getPrevious());
            self::assertSame(1, $operation->verifyCalls);
            self::assertSame(1, $operation->retireCalls);
        }
    }

    public function testOperationAndCleanupFailuresPreserveBothExactObjects(): void
    {
        $primary = new RuntimeException('synthetic primary failure');
        $cleanup = new RuntimeException('synthetic cleanup failure');
        $operation = new LifecycleOperation(null, $primary, $cleanup);

        try {
            (new AuthenticationOrchestrator([new LifecycleVerifier($this->mechanism(), null, $operation)]))
                ->authenticate($this->request());
            self::fail('Combined failure was not composed.');
        } catch (AuthenticationOperationAndCleanupException $failure) {
            self::assertSame($primary, $failure->operationFailure());
            self::assertSame($cleanup, $failure->cleanupFailure());
            self::assertSame($primary, $failure->getPrevious());
            self::assertSame(1, $operation->verifyCalls);
            self::assertSame(1, $operation->retireCalls);
        }
    }

    public function testSequentialCallsUseFreshOperationsWithoutRetainedCallState(): void
    {
        $verifier = new LifecycleVerifier($this->mechanism());
        $orchestrator = new AuthenticationOrchestrator([$verifier]);

        self::assertSame(AuthenticationOutcomeKind::Authenticated, $orchestrator->authenticate($this->request())->outcome()->kind());
        self::assertSame(AuthenticationOutcomeKind::Authenticated, $orchestrator->authenticate($this->request())->outcome()->kind());
        self::assertCount(2, $verifier->operations);
        self::assertNotSame($verifier->operations[0], $verifier->operations[1]);
        foreach ($verifier->operations as $operation) {
            self::assertSame(1, $operation->verifyCalls);
            self::assertSame(1, $operation->retireCalls);
        }
    }

    public function testReentrantCallUsesIndependentLocalOperationState(): void
    {
        $verifier = new ReentrantLifecycleVerifier($this->mechanism());
        $orchestrator = new AuthenticationOrchestrator([$verifier]);
        $verifier->orchestrator = $orchestrator;

        self::assertSame(AuthenticationOutcomeKind::Authenticated, $orchestrator->authenticate($this->request())->outcome()->kind());
        self::assertSame(2, $verifier->beginCalls);
        self::assertSame(['outer-verify', 'inner-verify', 'inner-retire', 'outer-retire'], $verifier->events);
    }

    private function request(): AuthenticationRequest
    {
        return new AuthenticationRequest(
            [new LifecycleEvidence($this->mechanism())],
            new AuthenticationInstant(150),
            new AuthenticationRequirement(new AuthenticationStrength(2), 50),
        );
    }

    private function mechanism(): AuthenticationMechanism
    {
        return new AuthenticationMechanism('synthetic.lifecycle');
    }
}

final readonly class LifecycleEvidence implements AuthenticationEvidence
{
    public function __construct(private AuthenticationMechanism $mechanism) {}

    public function mechanism(): AuthenticationMechanism
    {
        return $this->mechanism;
    }
}

class LifecycleVerifier implements AuthenticationVerifier
{
    public int $beginCalls = 0;

    /** @var list<LifecycleOperation> */
    public array $operations = [];

    public function __construct(
        private readonly AuthenticationMechanism $mechanism,
        private readonly ?Throwable $beginFailure = null,
        private readonly ?LifecycleOperation $fixedOperation = null,
    ) {}

    public function mechanism(): AuthenticationMechanism
    {
        return $this->mechanism;
    }

    public function begin(AuthenticationEvidence $evidence, AuthenticationInstant $now): AuthenticationVerificationOperation
    {
        ++$this->beginCalls;
        if (null !== $this->beginFailure) {
            throw $this->beginFailure;
        }

        $operation = $this->fixedOperation ?? new LifecycleOperation(self::authenticated($this->mechanism), null, null);
        $this->operations[] = $operation;

        return $operation;
    }

    public static function authenticated(AuthenticationMechanism $mechanism): AuthenticationVerification
    {
        return AuthenticationVerification::authenticated(new AuthenticationFacts(
            new PrincipalReference(PrincipalKind::Human, new PrincipalId('principal-lifecycle')),
            $mechanism,
            new AuthenticationStrength(2),
            new AuthenticationInstant(100),
            new AuthenticationInstant(150),
        ));
    }
}

final class LifecycleOperation implements AuthenticationVerificationOperation
{
    public int $verifyCalls = 0;
    public int $retireCalls = 0;

    public function __construct(
        private readonly ?AuthenticationVerification $verification,
        private readonly ?Throwable $verifyFailure,
        private readonly ?Throwable $retireFailure,
    ) {}

    public function verify(): AuthenticationVerification
    {
        ++$this->verifyCalls;
        if (null !== $this->verifyFailure) {
            throw $this->verifyFailure;
        }

        if (null === $this->verification) {
            throw new RuntimeException('Missing synthetic verification.');
        }

        return $this->verification;
    }

    public function retire(): void
    {
        ++$this->retireCalls;
        if (null !== $this->retireFailure) {
            throw $this->retireFailure;
        }
    }
}

final class ReentrantLifecycleVerifier implements AuthenticationVerifier
{
    public int $beginCalls = 0;
    public ?AuthenticationOrchestrator $orchestrator = null;

    /** @var list<string> */
    public array $events = [];

    public function __construct(private readonly AuthenticationMechanism $mechanism) {}

    public function mechanism(): AuthenticationMechanism
    {
        return $this->mechanism;
    }

    public function begin(AuthenticationEvidence $evidence, AuthenticationInstant $now): AuthenticationVerificationOperation
    {
        ++$this->beginCalls;

        return new ReentrantLifecycleOperation($this, 1 === $this->beginCalls);
    }

    public function request(): AuthenticationRequest
    {
        return new AuthenticationRequest(
            [new LifecycleEvidence($this->mechanism)],
            new AuthenticationInstant(150),
            new AuthenticationRequirement(new AuthenticationStrength(2), 50),
        );
    }
}

final readonly class ReentrantLifecycleOperation implements AuthenticationVerificationOperation
{
    public function __construct(
        private ReentrantLifecycleVerifier $verifier,
        private bool $outer,
    ) {}

    public function verify(): AuthenticationVerification
    {
        if ($this->outer) {
            $this->verifier->events[] = 'outer-verify';
            if (null === $this->verifier->orchestrator) {
                throw new RuntimeException('Missing reentrant orchestrator.');
            }
            $nested = $this->verifier->orchestrator->authenticate($this->verifier->request());
            if (AuthenticationOutcomeKind::Authenticated !== $nested->outcome()->kind()) {
                throw new RuntimeException('Unexpected nested outcome.');
            }
        } else {
            $this->verifier->events[] = 'inner-verify';
        }

        return LifecycleVerifier::authenticated($this->verifier->mechanism());
    }

    public function retire(): void
    {
        $this->verifier->events[] = $this->outer ? 'outer-retire' : 'inner-retire';
    }
}
