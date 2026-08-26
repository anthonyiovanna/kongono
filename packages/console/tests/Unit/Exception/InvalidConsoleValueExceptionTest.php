<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit\Exception;

use Kongono\Console\Diagnostic\ConsoleDiagnostic;
use Kongono\Console\Exception\ConsoleException;
use Kongono\Console\Exception\ExceptionInterface;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InvalidConsoleValueExceptionTest extends TestCase
{
    public function testFactoriesMapToExactDiagnosticsAndSafePresentation(): void
    {
        $factories = [
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::identifier(), ConsoleDiagnostic::Validation],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::summary(), ConsoleDiagnostic::Validation],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::definition(), ConsoleDiagnostic::Definition],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::input(), ConsoleDiagnostic::Validation],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::invocation(), ConsoleDiagnostic::Invocation],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::failure(), ConsoleDiagnostic::Failure],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::result(), ConsoleDiagnostic::Failure],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::lifecycle(), ConsoleDiagnostic::Lifecycle],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::specification(), ConsoleDiagnostic::Definition],
            [static fn(): InvalidConsoleValueException => InvalidConsoleValueException::parseResult(), ConsoleDiagnostic::Failure],
        ];

        foreach ($factories as [$factory, $diagnostic]) {
            $exception = $factory();
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertSame($diagnostic->title(), $exception->getMessage());
            self::assertSame($diagnostic->value . ': ' . $diagnostic->title(), (string) $exception);
        }
    }

    public function testBaseRetainsPreviousButNeverPresentsAdversarialMaterial(): void
    {
        $raw = 'token=never-present-this-value';
        $previous = new RuntimeException($raw);
        $exception = new class ($previous) extends ConsoleException {
            public function __construct(RuntimeException $previous)
            {
                parent::__construct(ConsoleDiagnostic::Lifecycle, $previous);
            }
        };

        self::assertSame($previous, $exception->getPrevious());
        self::assertStringNotContainsString($raw, $exception->getMessage());
        self::assertStringNotContainsString($raw, (string) $exception);
        foreach ($exception->diagnosticContext()->fields() as $field) {
            self::assertIsString($field->value());
            self::assertStringNotContainsString($raw, $field->value());
        }
    }
}
