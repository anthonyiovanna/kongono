<?php

declare(strict_types=1);

namespace Kongono\Authorization\Tests\Unit;

use Kongono\Authorization\AuthorizationDecisionReceipt;
use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationEvaluation;
use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AuthorizationEvaluationTest extends TestCase
{
    public function testDecisionIsInseparableFromExactReceipt(): void
    {
        $policy = new AuthorizationPolicyReference(
            new AuthorizationPolicyName('policy'),
            new AuthorizationPolicyVersion('v1'),
            new AuthorizationPolicyProvenance('source.one'),
        );
        $request = new AuthorizationRequest(
            SecurityContext::anonymous(),
            new Action('record.read'),
            new ResourceReference(new ResourceType('record'), new ResourceId('resource-evaluation')),
            $policy,
            new EvaluationPolicyEvidence($policy),
            new EvaluationEnvironmentEvidence(new AuthorizationEnvironmentProfile('facts.v1')),
        );
        $receipt = AuthorizationDecisionReceipt::evaluated($request, AuthorizationDecision::Deny);
        $evaluation = new AuthorizationEvaluation($receipt);

        self::assertSame(AuthorizationDecision::Deny, $evaluation->decision());
        self::assertSame($receipt, $evaluation->receipt());
        self::assertSame(['__construct', 'decision', 'receipt'], $this->declaredPublicMethods());
        self::assertSame(['receipt'], array_map(
            static fn($property): string => $property->getName(),
            (new ReflectionClass(AuthorizationEvaluation::class))->getProperties(),
        ));
    }

    /** @return list<string> */
    private function declaredPublicMethods(): array
    {
        $reflection = new ReflectionClass(AuthorizationEvaluation::class);
        $names = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && $method->getDeclaringClass()->getName() === AuthorizationEvaluation::class) {
                $names[] = $method->getName();
            }
        }
        sort($names);

        return $names;
    }
}

final readonly class EvaluationPolicyEvidence implements AuthorizationPolicyEvidence
{
    public function __construct(private AuthorizationPolicyReference $reference) {}

    public function reference(): AuthorizationPolicyReference
    {
        return $this->reference;
    }
}

final readonly class EvaluationEnvironmentEvidence implements AuthorizationEnvironmentEvidence
{
    public function __construct(private AuthorizationEnvironmentProfile $profile) {}

    public function profile(): AuthorizationEnvironmentProfile
    {
        return $this->profile;
    }
}
