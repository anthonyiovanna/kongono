<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Tests\Unit;

use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Composition\Exception\CompositionLifecycleException;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\SecurityContextState;
use Kongono\SecurityRuntime\Exception\InvalidGeneratedSecurityProfileException;
use Kongono\SecurityRuntime\Exception\SecurityRuntimeLifecycleException;
use Kongono\SecurityRuntime\GeneratedSecurityApplication;
use Kongono\SecurityRuntime\Tests\Fixture\InvalidMetadataApplicationAssembly;
use Kongono\SecurityRuntime\Tests\Fixture\TestApplicationAssembly;
use Kongono\SecurityRuntime\Tests\Fixture\TestOperationAssembly;
use Kongono\SecurityRuntime\Tests\Fixture\TestSecurityRoot;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

final class GeneratedSecurityApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        TestApplicationAssembly::reset();
        InvalidMetadataApplicationAssembly::$startCalls = 0;
    }

    public function testAnonymousAuthenticatedSequentialAndOverlappingOperationsAreIsolated(): void
    {
        $application = GeneratedSecurityApplication::start($this->loaded());
        $anonymous = $application->beginAnonymousOperation();
        $principal = self::principal('first-principal');
        $authenticated = $application->beginAuthenticatedOperation(self::verification($principal));
        self::assertSame(SecurityContextState::Anonymous, $anonymous->securityContext()->state());
        self::assertSame(SecurityContextState::Authenticated, $authenticated->securityContext()->state());
        self::assertSame($principal, $authenticated->securityContext()->principal());
        self::assertNotSame($anonymous->securityContext(), $authenticated->securityContext());
        $anonymousRoot = $anonymous->root();
        $authenticatedRoot = $authenticated->root();
        self::assertInstanceOf(TestSecurityRoot::class, $anonymousRoot);
        self::assertInstanceOf(TestSecurityRoot::class, $authenticatedRoot);
        self::assertSame($anonymous->securityContext(), $anonymousRoot->securityContext);
        self::assertSame($authenticated->securityContext(), $authenticatedRoot->securityContext);
        self::assertNotSame($anonymousRoot, $authenticatedRoot);

        $anonymous->close();
        self::assertSame($principal, $authenticated->securityContext()->principal());
        $authenticated->close();
        $afterAuthenticated = $application->beginAnonymousOperation();
        self::assertSame(SecurityContextState::Anonymous, $afterAuthenticated->securityContext()->state());
        self::assertNull($afterAuthenticated->securityContext()->principal());
        $afterAuthenticated->close();
        $afterAnonymous = $application->beginAuthenticatedOperation(self::verification(self::principal('second-principal')));
        self::assertSame(SecurityContextState::Authenticated, $afterAnonymous->securityContext()->state());
        $afterAnonymous->close();
        $application->shutdown();
    }

    public function testReentrantOperationEntryReceivesDistinctContextAndRoot(): void
    {
        $application = GeneratedSecurityApplication::start($this->loaded());
        $nested = null;
        TestApplicationAssembly::$onBegin = static function () use ($application, &$nested): void {
            TestApplicationAssembly::$onBegin = null;
            $nested = $application->beginAnonymousOperation();
        };
        $outer = $application->beginAnonymousOperation();
        self::assertNotNull($nested);
        self::assertNotSame($outer->securityContext(), $nested->securityContext());
        self::assertNotSame($outer->root(), $nested->root());
        $outer->close();
        self::assertSame(SecurityContextState::Anonymous, $nested->securityContext()->state());
        $nested->close();
        $application->shutdown();
    }

    public function testCloseClearsWrapperOnceAndPreservesPrimaryAndCleanupIdentity(): void
    {
        $application = GeneratedSecurityApplication::start($this->loaded());
        $operation = $application->beginAnonymousOperation();
        $primary = new RuntimeException('adversarial primary');
        $cleanup = new RuntimeException('adversarial cleanup');
        TestOperationAssembly::$cleanupFailure = $cleanup;
        try {
            $operation->close($primary);
            self::fail('Primary plus cleanup must be preserved.');
        } catch (CompositionLifecycleException $exception) {
            self::assertSame($primary, $exception->primaryFailure());
            self::assertSame([$cleanup], $exception->cleanupFailures());
            self::assertSame($primary, TestOperationAssembly::$lastPrimaryFailure);
        }
        self::assertTrue($operation->isClosed());
        self::assertSame(1, TestOperationAssembly::$closeCalls);
        $operation->close();
        self::assertSame(1, TestOperationAssembly::$closeCalls);
        try {
            $operation->root();
            self::fail('Closed root access must fail.');
        } catch (SecurityRuntimeLifecycleException $exception) {
            self::assertSame('KGN-SECURITY-RUNTIME-1003', $exception->diagnosticId());
        }
        try {
            $operation->securityContext();
            self::fail('Closed context access must fail.');
        } catch (SecurityRuntimeLifecycleException $exception) {
            self::assertSame('KGN-SECURITY-RUNTIME-1003', $exception->diagnosticId());
        }
        TestOperationAssembly::$cleanupFailure = null;
        $application->shutdown();
    }

    public function testRootExtractionFailureClosesAcquiredOperationWithExactFailure(): void
    {
        $application = GeneratedSecurityApplication::start($this->loaded());
        TestOperationAssembly::$failRootAccess = true;
        try {
            $application->beginAnonymousOperation();
            self::fail('Root extraction failure must escape.');
        } catch (RuntimeException $failure) {
            self::assertSame($failure, TestOperationAssembly::$lastPrimaryFailure);
            self::assertSame(1, TestOperationAssembly::$closeCalls);
        }
        TestOperationAssembly::$failRootAccess = false;
        $application->shutdown();
    }

    public function testProfileValidationOccursBeforeStartupAndApplicationLeaseProtectionRemains(): void
    {
        $invalid = new LoadedGeneratedWiring(
            str_repeat('b', 64),
            InvalidMetadataApplicationAssembly::class,
            TestOperationAssembly::class,
            new ReflectionMethod(InvalidMetadataApplicationAssembly::class, 'start'),
        );
        try {
            GeneratedSecurityApplication::start($invalid);
            self::fail('Invalid operation-input metadata must fail.');
        } catch (InvalidGeneratedSecurityProfileException) {
            self::assertSame(0, InvalidMetadataApplicationAssembly::$startCalls);
        }

        $application = GeneratedSecurityApplication::start($this->loaded());
        $operation = $application->beginAnonymousOperation();
        try {
            $application->shutdown();
            self::fail('Active generated operation must block shutdown.');
        } catch (LogicException) {
            self::assertFalse($application->isRetired());
        }
        $operation->close();
        $application->shutdown();
        self::assertTrue($application->isRetired());
        $this->expectException(SecurityRuntimeLifecycleException::class);
        $application->beginAnonymousOperation();
    }

    private function loaded(): LoadedGeneratedWiring
    {
        return new LoadedGeneratedWiring(
            str_repeat('a', 64),
            TestApplicationAssembly::class,
            TestOperationAssembly::class,
            new ReflectionMethod(TestApplicationAssembly::class, 'start'),
        );
    }

    private static function principal(string $id): PrincipalReference
    {
        return new PrincipalReference(PrincipalKind::Human, new PrincipalId($id));
    }

    private static function verification(PrincipalReference $principal): AuthenticationVerification
    {
        return AuthenticationVerification::authenticated(new AuthenticationFacts(
            $principal,
            new AuthenticationMechanism('test-mechanism'),
            new AuthenticationStrength(2),
            new AuthenticationInstant(100),
            new AuthenticationInstant(200),
        ));
    }
}
