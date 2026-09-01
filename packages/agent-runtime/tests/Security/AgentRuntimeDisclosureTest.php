<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Security;

use Kongono\AgentRuntime\Capability\AgentCapabilityOutcome;
use Kongono\AgentRuntime\Capability\AgentCapabilityResult;
use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use Kongono\AgentRuntime\Proposal\AgentProposalResult;
use Kongono\AgentRuntime\Tests\Support\AgentRuntimeFactory;
use PHPUnit\Framework\TestCase;

final class AgentRuntimeDisclosureTest extends TestCase
{
    public function testRawProposalCapabilityAndRejectedValuesNeverReachFailureSurfaces(): void
    {
        $secrets = ['SECRET-proposal-output', "SECRET-capability\x00output", 'SECRET-rejected-digest'];
        $capability = new AgentCapabilityResult(AgentCapabilityOutcome::Performed, $secrets[1], str_repeat('b', 64));
        self::assertSame($secrets[1], $capability->outputBytes());

        try {
            new AgentProposalResult(AgentRuntimeFactory::proposal(), 0, 0, $secrets[2]);
            self::fail('Invalid digest must reject.');
        } catch (InvalidAgentRuntimeException $failure) {
            foreach ($secrets as $secret) {
                self::assertStringNotContainsString($secret, $failure->getMessage());
                self::assertStringNotContainsString($secret, (string) $failure);
                foreach ($failure->diagnosticContext()->fields() as $field) {
                    self::assertStringNotContainsString($secret, (string) $field->value());
                }
            }
        }
    }
}
