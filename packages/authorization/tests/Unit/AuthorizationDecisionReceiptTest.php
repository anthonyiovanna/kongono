<?php

declare(strict_types=1);

namespace Kongono\Authorization\Tests\Unit;

use Kongono\Authorization\AuthorizationDecisionReceipt;
use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\AuthorizationReceiptReason;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AuthorizationDecisionReceiptTest extends TestCase
{
    public function testEvaluatedReceiptContainsOnlyExactBoundedFacts(): void
    {
        $request = $this->request();
        $receipt = AuthorizationDecisionReceipt::evaluated($request, AuthorizationDecision::Permit);

        self::assertSame(AuthorizationDecision::Permit, $receipt->decision());
        self::assertSame(AuthorizationReceiptReason::Evaluated, $receipt->reason());
        self::assertSame($request->expectedPolicy(), $receipt->policy());
        self::assertSame($request->action(), $receipt->action());
        self::assertSame($request->resource()->type(), $receipt->resourceType());
        self::assertSame($request->securityContext()->state(), $receipt->securityContextState());
        self::assertSame($request->environment()->profile(), $receipt->environmentProfile());

        $reflection = new ReflectionClass(AuthorizationDecisionReceipt::class);
        self::assertSame(
            ['action', 'decision', 'environmentProfile', 'policy', 'reason', 'resourceType', 'securityContextState'],
            $this->declaredProperties($reflection),
        );
        self::assertSame(
            ['action', 'decision', 'environmentProfile', 'evaluated', 'policy', 'policyMismatch', 'reason', 'resourceType', 'securityContextState', 'unsupportedPolicy'],
            $this->declaredPublicMethods($reflection),
        );
    }

    public function testMismatchAndUnsupportedReceiptsAreAlwaysIndeterminate(): void
    {
        $request = $this->request();
        $mismatch = AuthorizationDecisionReceipt::policyMismatch($request);
        $unsupported = AuthorizationDecisionReceipt::unsupportedPolicy($request);

        self::assertSame(AuthorizationDecision::Indeterminate, $mismatch->decision());
        self::assertSame(AuthorizationReceiptReason::PolicyMismatch, $mismatch->reason());
        self::assertSame(AuthorizationDecision::Indeterminate, $unsupported->decision());
        self::assertSame(AuthorizationReceiptReason::UnsupportedPolicy, $unsupported->reason());
    }

    private function request(): AuthorizationRequest
    {
        $policy = new AuthorizationPolicyReference(
            new AuthorizationPolicyName('policy'),
            new AuthorizationPolicyVersion('v1'),
            new AuthorizationPolicyProvenance('source.one'),
        );

        return new AuthorizationRequest(
            SecurityContext::anonymous(),
            new Action('record.read'),
            new ResourceReference(new ResourceType('record'), new ResourceId('resource-receipt')),
            $policy,
            new ReceiptPolicyEvidence($policy),
            new ReceiptEnvironmentEvidence(new AuthorizationEnvironmentProfile('facts.v1')),
        );
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredProperties(ReflectionClass $reflection): array
    {
        $names = array_map(static fn($property): string => $property->getName(), $reflection->getProperties());
        sort($names);

        return $names;
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethods(ReflectionClass $reflection): array
    {
        $names = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && $method->getDeclaringClass()->getName() === $reflection->getName()) {
                $names[] = $method->getName();
            }
        }
        sort($names);

        return $names;
    }
}

final readonly class ReceiptPolicyEvidence implements AuthorizationPolicyEvidence
{
    public function __construct(private AuthorizationPolicyReference $reference) {}

    public function reference(): AuthorizationPolicyReference
    {
        return $this->reference;
    }
}

final readonly class ReceiptEnvironmentEvidence implements AuthorizationEnvironmentEvidence
{
    public function __construct(private AuthorizationEnvironmentProfile $profile) {}

    public function profile(): AuthorizationEnvironmentProfile
    {
        return $this->profile;
    }
}
