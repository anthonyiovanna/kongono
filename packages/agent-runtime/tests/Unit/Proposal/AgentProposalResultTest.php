<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Unit\Proposal;

use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use Kongono\AgentRuntime\Proposal\AgentProposalResult;
use Kongono\AgentRuntime\Tests\Support\AgentRuntimeFactory;
use PHPUnit\Framework\TestCase;

final class AgentProposalResultTest extends TestCase
{
    public function testReportedUsageAndEvidenceAreExact(): void
    {
        $result = AgentRuntimeFactory::proposalResult(tokens: 7, outputBytes: 9);
        self::assertSame(7, $result->tokenUsage());
        self::assertSame(9, $result->outputByteUsage());
        self::assertSame(str_repeat('a', 64), $result->evidenceSha256());
    }

    public function testNegativeUsageAndInvalidDigestReject(): void
    {
        foreach ([[-1, 0, str_repeat('a', 64)], [0, -1, str_repeat('a', 64)], [0, 0, 'SECRET-invalid-digest']] as [$tokens, $bytes, $digest]) {
            try {
                new AgentProposalResult(AgentRuntimeFactory::proposal(), $tokens, $bytes, $digest);
                self::fail('Invalid proposal result must reject.');
            } catch (InvalidAgentRuntimeException $failure) {
                self::assertStringNotContainsString('SECRET', (string) $failure);
            }
        }
    }
}
