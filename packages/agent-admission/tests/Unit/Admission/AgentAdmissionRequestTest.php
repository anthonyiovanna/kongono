<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Unit\Admission;

use Kongono\AgentAdmission\Admission\AgentAdmissionRequest;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentAdmission\Tests\Support\AgentAdmissionFactory;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use PHPUnit\Framework\TestCase;

final class AgentAdmissionRequestTest extends TestCase
{
    public function testListsMustBeCanonicalUniqueActualListsAndEvidenceMustBeRequested(): void
    {
        $declaration = AgentAdmissionFactory::declaration();
        $artifact = (new AgentDeclarationArtifactBuilder())->build($declaration)->canonicalBytes();
        $verification = AgentAdmissionFactory::authenticated();

        foreach ([
            [[1 => new CapabilityIdentity('calendar.read')], []],
            [[new CapabilityIdentity('mail.read'), new CapabilityIdentity('calendar.read')], []],
            [[new CapabilityIdentity('calendar.read'), new CapabilityIdentity('calendar.read')], []],
            [[new CapabilityIdentity('calendar.read')], [AgentAdmissionFactory::evidence('mail.read')]],
        ] as [$requested, $evidence]) {
            try {
                new AgentAdmissionRequest($declaration, $artifact, $verification, $requested, $evidence);
                self::fail('Expected request rejection.');
            } catch (InvalidAgentAdmissionException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testListsAreBoundedAndDefensive(): void
    {
        $requested = [];
        for ($index = 0; $index < 33; ++$index) {
            $requested[] = new CapabilityIdentity(sprintf('capability.%02d', $index));
        }
        try {
            AgentAdmissionFactory::request(requested: $requested);
            self::fail('Expected limit failure.');
        } catch (InvalidAgentAdmissionException $failure) {
            $fields = [];
            foreach ($failure->diagnosticContext()->fields() as $field) {
                $fields[$field->key()] = $field->value();
            }
            self::assertSame(33, $fields['entry_count']);
        }

        $request = AgentAdmissionFactory::request(requested: [new CapabilityIdentity('calendar.read')]);
        $copy = $request->requestedCapabilities();
        $copy[] = new CapabilityIdentity('mail.read');
        self::assertCount(1, $request->requestedCapabilities());
        self::assertSame([], $request->authorizationEvidence());
    }
}
