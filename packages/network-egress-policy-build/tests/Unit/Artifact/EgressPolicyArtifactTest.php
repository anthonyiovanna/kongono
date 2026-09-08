<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Artifact;

use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Tests\Support\EgressPolicySourceFactory;
use PHPUnit\Framework\TestCase;

final class EgressPolicyArtifactTest extends TestCase
{
    public function testReferencePreservesIdentityAndConvertsOnlyDigestRepresentation(): void
    {
        $artifact = (new EgressPolicyArtifactBuilder())->build(EgressPolicySourceFactory::create(identity: 'application.policy-1'));
        $reference = $artifact->policyReference();
        self::assertSame($artifact->policyIdentity()->value(), $reference->identity());
        self::assertSame(strtoupper($artifact->sourceSha256()), $reference->sha256());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $artifact->sourceSha256());
        self::assertMatchesRegularExpression('/\A[0-9A-F]{64}\z/D', $reference->sha256());
        self::assertSame(strtolower($reference->sha256()), $artifact->sourceSha256());
    }
}
