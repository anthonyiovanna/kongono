<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement\Tests\Unit;

use Kongono\Authorization\AuthorizationEngine;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\Authorization\Exception\AuthorizationEvaluationException;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\SecurityEnforcement\Exception\ProtectedCapabilityEnforcementException;
use Kongono\SecurityEnforcement\ProtectedCapabilityEnforcer;
use Kongono\SecurityEnforcement\Tests\Fixture\CallbackAuthorizationEvaluator;
use Kongono\SecurityEnforcement\Tests\Fixture\CallbackProtectedCapability;
use Kongono\SecurityEnforcement\Tests\Fixture\TestAuthorizationEnvironmentEvidence;
use Kongono\SecurityEnforcement\Tests\Fixture\TestAuthorizationPolicyEvidence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProtectedCapabilityEnforcerTest extends TestCase
{
    public function testExactPermitEvaluatesOnceAndReturnsExactCapabilityResult(): void
    {
        $fixture = $this->fixture(AuthorizationDecision::Permit);
        $result = new \stdClass();
        $executions = 0;
        $capability = new CallbackProtectedCapability(
            $fixture['action'],
            $fixture['resource'],
            static function () use (&$executions, $result): object {
                ++$executions;

                return $result;
            },
        );

        $actual = (new ProtectedCapabilityEnforcer())->enforce(
            $fixture['context'],
            $fixture['engine'],
            $fixture['request'],
            $capability,
        );

        self::assertSame($result, $actual);
        self::assertSame(1, $fixture['evaluations']());
        self::assertSame(1, $executions);
    }

    /** @return iterable<string, array{AuthorizationDecision}> */
    public static function rejectedDecisions(): iterable
    {
        yield 'deny' => [AuthorizationDecision::Deny];
        yield 'indeterminate' => [AuthorizationDecision::Indeterminate];
    }

    #[DataProvider('rejectedDecisions')]
    public function testNonPermitDecisionNeverExecutes(AuthorizationDecision $decision): void
    {
        $fixture = $this->fixture($decision);
        $executions = 0;
        $capability = new CallbackProtectedCapability(
            $fixture['action'],
            $fixture['resource'],
            static function () use (&$executions): void {
                ++$executions;
            },
        );

        try {
            (new ProtectedCapabilityEnforcer())->enforce($fixture['context'], $fixture['engine'], $fixture['request'], $capability);
            self::fail('Expected rejection.');
        } catch (ProtectedCapabilityEnforcementException $failure) {
            self::assertSame('KGN-SECURITY-ENFORCEMENT-1004', $failure->diagnosticId());
        }

        self::assertSame(1, $fixture['evaluations']());
        self::assertSame(0, $executions);
    }

    public function testEveryBindingMismatchStopsBeforeEvaluationOrExecution(): void
    {
        $enforcer = new ProtectedCapabilityEnforcer();

        foreach (['context', 'action', 'resource'] as $mismatch) {
            $fixture = $this->fixture(AuthorizationDecision::Permit);
            $executions = 0;
            $context = 'context' === $mismatch ? SecurityContext::anonymous() : $fixture['context'];
            $action = 'action' === $mismatch ? new Action('record.write') : $fixture['action'];
            $resource = 'resource' === $mismatch
                ? new ResourceReference(new ResourceType('record'), new ResourceId('other'))
                : $fixture['resource'];
            $capability = new CallbackProtectedCapability($action, $resource, static function () use (&$executions): void {
                ++$executions;
            });

            try {
                $enforcer->enforce($context, $fixture['engine'], $fixture['request'], $capability);
                self::fail('Expected binding rejection.');
            } catch (ProtectedCapabilityEnforcementException) {
                self::assertSame(0, $fixture['evaluations']());
                self::assertSame(0, $executions);
            }
        }
    }

    public function testAuthorizationFailureAndCapabilityFailurePreserveTheirExactCauses(): void
    {
        $fixture = $this->fixture(AuthorizationDecision::Permit, new RuntimeException('engine-secret'));
        $executions = 0;
        $capability = new CallbackProtectedCapability($fixture['action'], $fixture['resource'], static function () use (&$executions): void {
            ++$executions;
        });

        try {
            (new ProtectedCapabilityEnforcer())->enforce($fixture['context'], $fixture['engine'], $fixture['request'], $capability);
            self::fail('Expected authorization failure.');
        } catch (AuthorizationEvaluationException $failure) {
            self::assertSame($fixture['engineFailure'], $failure->getPrevious());
        }
        self::assertSame(0, $executions);

        $fixture = $this->fixture(AuthorizationDecision::Permit);
        $capabilityFailure = new RuntimeException('capability-secret');
        $capability = new CallbackProtectedCapability($fixture['action'], $fixture['resource'], static function () use ($capabilityFailure): never {
            throw $capabilityFailure;
        });
        $actualFailure = null;
        try {
            (new ProtectedCapabilityEnforcer())->enforce($fixture['context'], $fixture['engine'], $fixture['request'], $capability);
        } catch (RuntimeException $failure) {
            $actualFailure = $failure;
        }
        self::assertSame($capabilityFailure, $actualFailure);
        self::assertSame(1, $fixture['evaluations']());
    }

    public function testSequentialAndReentrantCallsShareNoDecisionOrResult(): void
    {
        $enforcer = new ProtectedCapabilityEnforcer();
        $inner = $this->fixture(AuthorizationDecision::Permit);
        $innerExecutions = 0;
        $innerCapability = new CallbackProtectedCapability(
            $inner['action'],
            $inner['resource'],
            static function () use (&$innerExecutions): string {
                ++$innerExecutions;

                return 'inner';
            },
        );

        $outer = $this->fixture(AuthorizationDecision::Permit, null, static function () use ($enforcer, $inner, $innerCapability): void {
            self::assertSame('inner', $enforcer->enforce($inner['context'], $inner['engine'], $inner['request'], $innerCapability));
        });
        $outerExecutions = 0;
        $outerCapability = new CallbackProtectedCapability(
            $outer['action'],
            $outer['resource'],
            static function () use (&$outerExecutions): string {
                ++$outerExecutions;

                return 'outer';
            },
        );

        self::assertSame('outer', $enforcer->enforce($outer['context'], $outer['engine'], $outer['request'], $outerCapability));
        self::assertSame(1, $outer['evaluations']());
        self::assertSame(1, $inner['evaluations']());
        self::assertSame(1, $outerExecutions);
        self::assertSame(1, $innerExecutions);

        $denied = $this->fixture(AuthorizationDecision::Deny);
        $this->expectException(ProtectedCapabilityEnforcementException::class);
        $enforcer->enforce($denied['context'], $denied['engine'], $denied['request'], $outerCapability);
    }

    /**
     * @param null|callable(): void $duringEvaluation
     * @return array{context: SecurityContext, action: Action, resource: ResourceReference, request: AuthorizationRequest, engine: AuthorizationEngine, evaluations: callable(): int, engineFailure: ?RuntimeException}
     */
    private function fixture(
        AuthorizationDecision $decision,
        ?RuntimeException $engineFailure = null,
        ?callable $duringEvaluation = null,
    ): array {
        $context = SecurityContext::anonymous();
        $action = new Action('record.read');
        $resource = new ResourceReference(new ResourceType('record'), new ResourceId('record-1'));
        $policy = new AuthorizationPolicyReference(
            new AuthorizationPolicyName('records'),
            new AuthorizationPolicyVersion('v1'),
            new AuthorizationPolicyProvenance('fixture'),
        );
        $request = new AuthorizationRequest(
            $context,
            $action,
            $resource,
            $policy,
            new TestAuthorizationPolicyEvidence($policy),
            new TestAuthorizationEnvironmentEvidence(new AuthorizationEnvironmentProfile('fixture')),
        );
        $evaluations = 0;
        $evaluator = new CallbackAuthorizationEvaluator(
            $policy,
            static function (AuthorizationRequest $actual) use (&$evaluations, $request, $decision, $engineFailure, $duringEvaluation): AuthorizationDecision {
                ++$evaluations;
                self::assertSame($request, $actual);
                if (null !== $duringEvaluation) {
                    $duringEvaluation();
                }
                if (null !== $engineFailure) {
                    throw $engineFailure;
                }

                return $decision;
            },
        );

        return [
            'context' => $context,
            'action' => $action,
            'resource' => $resource,
            'request' => $request,
            'engine' => new AuthorizationEngine([$evaluator]),
            'evaluations' => static function () use (&$evaluations): int {
                return $evaluations;
            },
            'engineFailure' => $engineFailure,
        ];
    }
}
