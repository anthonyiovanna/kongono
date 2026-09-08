<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Security;

use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicyArtifactException;
use Kongono\NetworkEgressPolicyBuild\Tests\Support\EgressPolicySourceFactory;
use PHPUnit\Framework\TestCase;

final class NetworkEgressPolicyBuildDisclosureTest extends TestCase
{
    public function testProtectedIdentityOriginArtifactAndDigestsNeverEnterFailurePresentation(): void
    {
        $artifact = (new EgressPolicyArtifactBuilder())->build(EgressPolicySourceFactory::create(identity: 'protected.policy'));
        foreach (['protected.policy', 'api.example.test', $artifact->canonicalBytes(), $artifact->artifactSha256(), $artifact->sourceSha256()] as $protected) {
            try {
                (new EgressPolicyArtifactValidator())->validateStructure($artifact->canonicalBytes() . $protected);
                self::fail('Expected invalid artifact.');
            } catch (InvalidEgressPolicyArtifactException $failure) {
                self::assertStringNotContainsString($protected, $failure->getMessage());
                self::assertStringNotContainsString($protected, (string) $failure);
                self::assertStringNotContainsString($protected, $failure->diagnosticRemediation());
            }
        }
    }
}
