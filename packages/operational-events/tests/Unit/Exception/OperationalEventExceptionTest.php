<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Tests\Unit\Exception;

use Kongono\OperationalEvents\Exception\ExceptionInterface;
use Kongono\OperationalEvents\Exception\InvalidOperationalEventSchemaException;
use Kongono\OperationalEvents\Exception\InvalidOperationalEventValueException;
use Kongono\OperationalEvents\Exception\OperationalEventException;
use Kongono\OperationalEvents\Exception\OperationalEventRecordingException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class OperationalEventExceptionTest extends TestCase
{
    public function testCatchBoundaryFactoriesAndNondisclosureAreExact(): void
    {
        $marker = new ReflectionClass(ExceptionInterface::class);
        $base = new ReflectionClass(OperationalEventException::class);
        self::assertContains(\Throwable::class, $marker->getInterfaceNames());
        self::assertTrue($base->isAbstract());
        $previous = new RuntimeException('protected-recorder-message');
        $exceptions = [
            InvalidOperationalEventSchemaException::field(),
            InvalidOperationalEventSchemaException::schema(),
            InvalidOperationalEventValueException::attributes(),
            OperationalEventRecordingException::failed($previous),
        ];
        foreach ($exceptions as $exception) {
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
            self::assertSame($exception->diagnosticId() . ': ' . $exception->diagnosticTitle(), (string) $exception);
            self::assertStringNotContainsString('protected-recorder-message', $exception->getMessage());
            self::assertStringNotContainsString('protected-recorder-message', (string) $exception);
            foreach ($exception->diagnosticContext()->fields() as $field) {
                self::assertNotSame('protected-recorder-message', $field->value());
            }
        }
        self::assertSame($previous, $exceptions[3]->getPrevious());
    }
}
