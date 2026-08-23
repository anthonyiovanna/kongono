<?php

declare(strict_types=1);

namespace Kongono\Oshie\Tests\Unit\Diagnostic;

use InvalidArgumentException;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\ArtifactUnavailableException;
use Kongono\Oshie\Exception\ExceptionInterface;
use Kongono\Oshie\Exception\InvalidOshieValueException;
use PHPUnit\Framework\TestCase;

final class OshieDiagnosticTest extends TestCase
{
    public function testDiagnosticIdsAndSafeSchemasAreExact(): void
    {
        self::assertSame(['KGN-OSHIE-1001', 'KGN-OSHIE-1002', 'KGN-OSHIE-1003', 'KGN-OSHIE-1004', 'KGN-OSHIE-1005', 'KGN-OSHIE-1006', 'KGN-OSHIE-1007', 'KGN-OSHIE-1008', 'KGN-OSHIE-1009'], array_map(static fn(OshieDiagnostic $diagnostic): string => $diagnostic->value, OshieDiagnostic::cases()));
        foreach (OshieDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->operation());
            self::assertNotSame('', $diagnostic->violatedInvariant());
        }
        $context = OshieDiagnostic::ArtifactUnavailable->context('artifact-path');
        self::assertCount(1, $context->fields());
        self::assertSame(DiagnosticDisclosure::Safe, $context->fields()[0]->disclosure());
    }

    public function testExceptionMarkerAndNonDisclosureRemainSeparated(): void
    {
        $exception = new ArtifactUnavailableException(OshieDiagnostic::ArtifactUnavailable, 'artifact-path', new \RuntimeException('secret'));
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertStringNotContainsString('secret', (string) $exception);
        $value = InvalidOshieValueException::response();
        self::assertInstanceOf(InvalidArgumentException::class, $value);
        self::assertNotContains(ExceptionInterface::class, (new \ReflectionClass($value))->getInterfaceNames());
    }
}
