<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Unit\Capability;

use Kongono\AgentRuntime\Capability\AgentCapabilityOutcome;
use Kongono\AgentRuntime\Capability\AgentCapabilityResult;
use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use PHPUnit\Framework\TestCase;

final class AgentCapabilityResultTest extends TestCase
{
    public function testBinaryOutputAndOutcomeRemainExact(): void
    {
        $bytes = "\x00\xFFresult";
        $result = new AgentCapabilityResult(AgentCapabilityOutcome::Failed, $bytes, str_repeat('a', 64));
        self::assertSame(AgentCapabilityOutcome::Failed, $result->outcome());
        self::assertSame($bytes, $result->outputBytes());
    }

    public function testHardBoundAndDigestGrammarFailClosed(): void
    {
        $this->expectException(InvalidAgentRuntimeException::class);
        new AgentCapabilityResult(AgentCapabilityOutcome::Performed, str_repeat('x', 1_048_577), str_repeat('a', 64));
    }
}
