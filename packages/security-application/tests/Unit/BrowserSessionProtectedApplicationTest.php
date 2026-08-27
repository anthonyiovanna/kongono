<?php

declare(strict_types=1);

namespace Kongono\SecurityApplication\Tests\Unit;

use Closure;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationOrchestrator;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authorization\AuthorizationEngine;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\SecurityApplication\BrowserSessionProtectedApplication;
use Kongono\SecurityEnforcement\Exception\ProtectedCapabilityEnforcementException;
use Kongono\SecurityEnforcement\ProtectedCapabilityEnforcer;
use Kongono\SecurityEnforcement\Tests\Fixture\CallbackAuthorizationEvaluator;
use Kongono\SecurityEnforcement\Tests\Fixture\CallbackProtectedCapability;
use Kongono\SecurityEnforcement\Tests\Fixture\TestAuthorizationEnvironmentEvidence;
use Kongono\SecurityHttp\BrowserSessionSecurityGateway;
use Kongono\SecurityHttp\Tests\Fixture\TestApplicationAssembly;
use Kongono\SecurityHttp\Tests\Fixture\TestBrowserSessionVerifier;
use Kongono\SecurityHttp\Tests\Fixture\TestOperationAssembly;
use Kongono\SecurityHttp\TrustedOrigin;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;
use Kongono\SecurityRuntime\GeneratedSecurityApplication;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class BrowserSessionProtectedApplicationTest extends TestCase
{
    private ?string $root = null;

    protected function setUp(): void
    {
        TestApplicationAssembly::reset();
    }

    protected function tearDown(): void
    {
        if (null !== $this->root) {
            $artifact = glob($this->root . '/var/build/*.json');
            self::assertIsArray($artifact);
            foreach ($artifact as $path) {
                unlink($path);
            }
            rmdir($this->root . '/var/build');
            rmdir($this->root . '/var');
            unlink($this->root . '/kongono.security-policy.json');
            rmdir($this->root);
        }
    }

    public function testExactPermitUsesOneContextEvaluatesAndExecutesOnceThenCloses(): void
    {
        $evaluations = 0;
        $executions = 0;
        [$application, $capability] = $this->fixture(
            static function (AuthorizationRequest $request) use (&$evaluations): AuthorizationDecision {
                ++$evaluations;
                self::assertSame($request->securityContext(), $request->securityContext());

                return AuthorizationDecision::Permit;
            },
            static function () use (&$executions): string {
                ++$executions;

                return 'bounded-result';
            },
        );

        self::assertSame('bounded-result', $application->execute(
            new ServerRequest('GET', 'https://example.test/path'),
            new AuthenticationInstant(100),
            new TestAuthorizationEnvironmentEvidence(new \Kongono\Authorization\AuthorizationEnvironmentProfile('public-example')),
            $capability,
        ));
        self::assertSame(1, $evaluations);
        self::assertSame(1, $executions);
        self::assertSame(1, TestApplicationAssembly::$beginCalls);
        self::assertSame(1, TestOperationAssembly::$closeCalls);
    }

    /** @param callable(): AuthorizationDecision $decision */
    #[DataProvider('rejectedDecisions')]
    public function testOnlyExactPermitExecutes(callable $decision): void
    {
        $executions = 0;
        [$application, $capability] = $this->fixture(
            static fn(AuthorizationRequest $request): AuthorizationDecision => $decision(),
            static function () use (&$executions): void {
                ++$executions;
            },
        );
        try {
            $application->execute(
                new ServerRequest('GET', 'https://example.test/path'),
                new AuthenticationInstant(100),
                new TestAuthorizationEnvironmentEvidence(new \Kongono\Authorization\AuthorizationEnvironmentProfile('public-example')),
                $capability,
            );
            self::fail('Expected deny-by-default enforcement failure.');
        } catch (ProtectedCapabilityEnforcementException) {
            self::assertSame(0, $executions);
            self::assertSame(1, TestOperationAssembly::$closeCalls);
        }
    }

    /** @return iterable<string, array{callable(): AuthorizationDecision}> */
    public static function rejectedDecisions(): iterable
    {
        yield 'deny' => [static fn(): AuthorizationDecision => AuthorizationDecision::Deny];
        yield 'indeterminate' => [static fn(): AuthorizationDecision => AuthorizationDecision::Indeterminate];
    }

    /** @template TResult
     * @param Closure(AuthorizationRequest): AuthorizationDecision $evaluate
     * @param Closure(): TResult $execute
     * @return array{BrowserSessionProtectedApplication, CallbackProtectedCapability<TResult>}
     */
    private function fixture(Closure $evaluate, Closure $execute): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-security-application-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($root . '/var/build', 0700, true));
        $source = '{"schema":"kongono.security-policy","version":1,"policy":{"name":"example.public","version":"v1","provenance":"application-owned"}}' . "\n";
        self::assertSame(strlen($source), file_put_contents($root . '/kongono.security-policy.json', $source));
        $this->root = $root;
        $policy = (new SecurityPolicyBuildWorkflow())->build($root, $root . '/var/build');
        $action = new Action('example.read');
        $resource = new ResourceReference(new ResourceType('example'), new ResourceId('public'));
        $engine = new AuthorizationEngine([new CallbackAuthorizationEvaluator($policy->evidence()->reference(), $evaluate)]);
        $capability = new CallbackProtectedCapability($action, $resource, $execute);
        $verifier = new TestBrowserSessionVerifier(AuthenticationVerification::invalid());
        $generated = GeneratedSecurityApplication::start(new LoadedGeneratedWiring(
            str_repeat('a', 64),
            TestApplicationAssembly::class,
            TestOperationAssembly::class,
            new ReflectionMethod(TestApplicationAssembly::class, 'start'),
        ));
        $gateway = new BrowserSessionSecurityGateway(
            new TrustedOrigin('https://example.test'),
            new AuthenticationOrchestrator([$verifier]),
            new AuthenticationRequirement(new AuthenticationStrength(1), 300),
            $generated,
        );

        return [new BrowserSessionProtectedApplication($gateway, $engine, new ProtectedCapabilityEnforcer(), $policy), $capability];
    }
}
