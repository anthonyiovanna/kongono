<?php

declare(strict_types=1);

namespace Kongono\Authorization\Tests\Unit;

use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AuthorizationRequestTest extends TestCase
{
    public function testConstructorOrderAndExactObjectIdentitiesArePreserved(): void
    {
        $securityContext = SecurityContext::anonymous();
        $action = new Action('record.read');
        $resource = new ResourceReference(new ResourceType('record'), new ResourceId('resource-request'));
        $policy = $this->policy();
        $policyEvidence = new RequestPolicyEvidence($policy);
        $environment = new RequestEnvironmentEvidence(new AuthorizationEnvironmentProfile('facts.v1'));
        $request = new AuthorizationRequest($securityContext, $action, $resource, $policy, $policyEvidence, $environment);

        self::assertSame($securityContext, $request->securityContext());
        self::assertSame($action, $request->action());
        self::assertSame($resource, $request->resource());
        self::assertSame($policy, $request->expectedPolicy());
        self::assertSame($policyEvidence, $request->policyEvidence());
        self::assertSame($environment, $request->environment());

        $constructor = (new ReflectionClass(AuthorizationRequest::class))->getConstructor();
        self::assertNotNull($constructor);
        self::assertSame(
            ['securityContext', 'action', 'resource', 'expectedPolicy', 'policyEvidence', 'environment'],
            array_map(static fn($parameter): string => $parameter->getName(), $constructor->getParameters()),
        );
    }

    private function policy(): AuthorizationPolicyReference
    {
        return new AuthorizationPolicyReference(
            new AuthorizationPolicyName('policy'),
            new AuthorizationPolicyVersion('v1'),
            new AuthorizationPolicyProvenance('source.one'),
        );
    }
}

final readonly class RequestPolicyEvidence implements AuthorizationPolicyEvidence
{
    public function __construct(private AuthorizationPolicyReference $reference) {}

    public function reference(): AuthorizationPolicyReference
    {
        return $this->reference;
    }
}

final readonly class RequestEnvironmentEvidence implements AuthorizationEnvironmentEvidence
{
    public function __construct(private AuthorizationEnvironmentProfile $profile) {}

    public function profile(): AuthorizationEnvironmentProfile
    {
        return $this->profile;
    }
}
