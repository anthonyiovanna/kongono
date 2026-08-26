<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Exception;

use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Exception\ConsoleBuildException;
use Kongono\ConsoleBuild\Exception\ExceptionInterface;
use Kongono\ConsoleBuild\Exception\InvalidConsoleArtifactException;
use Kongono\ConsoleBuild\Exception\InvalidConsoleSourceException;
use Kongono\ConsoleBuild\Exception\StaleConsoleArtifactException;
use Kongono\ConsoleBuild\Internal\ConsoleBuildFailureCategory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConsoleBuildExceptionTest extends TestCase
{
    public function testConcreteFactoriesExposeOnlyDeterministicPackageEvidence(): void
    {
        $exceptions = [
            InvalidConsoleSourceException::invalid(ConsoleBuildDiagnostic::InvalidSource, ConsoleBuildFailureCategory::SourceJson),
            InvalidConsoleSourceException::invalid(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model),
            InvalidConsoleArtifactException::invalid(ConsoleBuildFailureCategory::ArtifactStructure),
            StaleConsoleArtifactException::currentMismatch(),
        ];

        foreach ($exceptions as $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($exception->diagnosticTitle(), $exception->getMessage());
            self::assertSame($exception->diagnosticId() . ': ' . $exception->diagnosticTitle(), (string) $exception);
            self::assertNotSame('', $exception->diagnosticRemediation());
            self::assertCount(3, $exception->diagnosticContext()->fields());
        }
    }

    public function testPreviousNativeFailureIsRetainedButNeverStringified(): void
    {
        $secret = 'native-secret-command-value';
        $previous = new RuntimeException($secret);
        $exception = new class ($previous) extends ConsoleBuildException {
            public function __construct(RuntimeException $previous)
            {
                parent::__construct(ConsoleBuildDiagnostic::InvalidArtifact, ConsoleBuildFailureCategory::ArtifactStructure, false, $previous);
            }
        };

        self::assertSame($previous, $exception->getPrevious());
        self::assertStringNotContainsString($secret, $exception->getMessage());
        self::assertStringNotContainsString($secret, (string) $exception);
        foreach ($exception->diagnosticContext()->fields() as $field) {
            self::assertStringNotContainsString($secret, (string) $field->value());
        }
    }
}
