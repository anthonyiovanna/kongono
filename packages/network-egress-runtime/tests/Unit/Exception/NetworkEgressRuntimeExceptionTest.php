<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Unit\Exception;

use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use PHPUnit\Framework\TestCase;

final class NetworkEgressRuntimeExceptionTest extends TestCase
{
    public function testStringificationIsBoundedDeterministicAndTraceFree(): void
    {
        $failure = InvalidEgressRuntimeException::invalid('address_grammar');
        self::assertSame('KGN-EGRESS-RUNTIME-1520', $failure->diagnosticId());
        self::assertSame($failure::class . ': [KGN-EGRESS-RUNTIME-1520] Network egress runtime input is invalid.', (string) $failure);
        self::assertStringNotContainsString(__FILE__, (string) $failure);
        self::assertLessThanOrEqual(256, strlen((string) $failure));
    }
}
