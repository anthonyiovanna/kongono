<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Security;

use Kongono\AgentAdmission\Admission\AgentAdmissionRequest;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentAdmission\Tests\Support\AgentAdmissionFactory;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use PHPUnit\Framework\TestCase;

final class AgentAdmissionDisclosureTest extends TestCase
{
    public function testRejectedLiteralArtifactAndCapabilityValuesNeverEnterFailureSurfaces(): void
    {
        $secret = 'RAW-SECRET-CAPABILITY';
        try {
            new CapabilityIdentity($secret);
            self::fail('Expected contract rejection.');
        } catch (\Throwable $failure) {
            self::assertStringNotContainsString($secret, $failure->getMessage());
            self::assertStringNotContainsString($secret, (string) $failure);
        }

        $declaration = AgentAdmissionFactory::declaration();
        $artifact = (new AgentDeclarationArtifactBuilder())->build($declaration)->canonicalBytes();
        try {
            new AgentAdmissionRequest(
                $declaration,
                $artifact,
                AgentAdmissionFactory::authenticated(),
                [new CapabilityIdentity('calendar.read')],
                [AgentAdmissionFactory::evidence('mail.read')],
            );
            self::fail('Expected unmatched evidence failure.');
        } catch (InvalidAgentAdmissionException $failure) {
            $surface = $failure->getMessage() . (string) $failure . json_encode($failure->diagnosticContext()->fields());
            self::assertStringNotContainsString('mail.read', $surface);
            self::assertStringNotContainsString('agent-primary', $surface);
        }
    }
}
