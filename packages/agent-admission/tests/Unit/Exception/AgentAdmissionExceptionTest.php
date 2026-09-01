<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Unit\Exception;

use InvalidArgumentException;
use Kongono\AgentAdmission\Exception\ExceptionInterface;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AgentAdmissionExceptionTest extends TestCase
{
    public function testExceptionMarkerCompatibilityAndStringificationAreDeterministic(): void
    {
        $previous = new RuntimeException('RAW-SECRET-PREVIOUS');
        $failure = InvalidAgentAdmissionException::invalid('not-allowlisted', 2, 1, $previous);
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertInstanceOf(InvalidArgumentException::class, $failure);
        self::assertSame('unsupported_violation', $failure->diagnosticContext()->fields()[0]->value());
        self::assertStringNotContainsString('RAW-SECRET-PREVIOUS', $failure->getMessage());
        self::assertStringNotContainsString('RAW-SECRET-PREVIOUS', (string) $failure);
        self::assertStringContainsString($failure->diagnosticId(), (string) $failure);
    }
}
