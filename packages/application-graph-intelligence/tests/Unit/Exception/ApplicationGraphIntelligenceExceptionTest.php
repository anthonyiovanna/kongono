<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Tests\Unit\Exception;

use Kongono\ApplicationGraphIntelligence\Exception\ExceptionInterface;
use Kongono\ApplicationGraphIntelligence\Exception\InvalidApplicationGraphIntelligenceValueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApplicationGraphIntelligenceExceptionTest extends TestCase
{
    public function testFactoriesPairDiagnosticsRetainCauseAndNeverDiscloseCallerValues(): void
    {
        $raw = 'TOP-SECRET-/workspace/customer.php';
        $cause = new RuntimeException($raw);
        $exceptions = [
            InvalidApplicationGraphIntelligenceValueException::view($raw),
            InvalidApplicationGraphIntelligenceValueException::verification($raw, $cause),
        ];

        self::assertSame([
            'KGN-APPLICATION-GRAPH-INTELLIGENCE-1001',
            'KGN-APPLICATION-GRAPH-INTELLIGENCE-1002',
        ], array_map(static fn(ExceptionInterface $exception): string => $exception->diagnosticId(), $exceptions));
        self::assertSame($cause, $exceptions[1]->getPrevious());
        foreach ($exceptions as $exception) {
            self::assertStringNotContainsString($raw, $exception->getMessage());
            self::assertStringNotContainsString($raw, (string) $exception);
            self::assertSame('unsupported_component', $exception->diagnosticContext()->fields()[0]->value());
        }
    }
}
