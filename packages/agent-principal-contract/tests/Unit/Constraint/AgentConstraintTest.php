<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Constraint;

use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use PHPUnit\Framework\TestCase;

final class AgentConstraintTest extends TestCase
{
    public function testClosedVocabularyAndIntegerBoundsAreExact(): void
    {
        self::assertSame([
            'step_limit',
            'duration_milliseconds_limit',
            'token_limit',
            'capability_call_limit',
            'retry_limit',
            'delegation_depth_limit',
            'output_bytes_limit',
            'retained_state_bytes_limit',
            'concurrency_limit',
        ], array_map(static fn(ConstraintKind $case): string => $case->value, ConstraintKind::cases()));

        $zero = new AgentConstraint(ConstraintKind::StepLimit, 0);
        $maximum = new AgentConstraint(ConstraintKind::TokenLimit, PHP_INT_MAX);
        self::assertSame(ConstraintKind::StepLimit, $zero->kind());
        self::assertSame(0, $zero->value());
        self::assertSame(PHP_INT_MAX, $maximum->value());
    }

    public function testNegativeValueFailsWithoutDisclosure(): void
    {
        try {
            new AgentConstraint(ConstraintKind::RetryLimit, -31337);
            self::fail('A negative constraint value was accepted.');
        } catch (InvalidAgentPrincipalContractException $exception) {
            self::assertSame('KGN-AGENT-CONTRACT-1305', $exception->diagnosticId());
            self::assertStringNotContainsString('-31337', $exception->getMessage());
            self::assertStringNotContainsString('-31337', (string) $exception);
        }
    }
}
