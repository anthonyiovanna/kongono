<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit\Exception;

use Kongono\Tracing\Exception\ExceptionInterface;
use Kongono\Tracing\Exception\InvalidTracingValueException;
use Kongono\Tracing\Exception\TraceRecordingException;
use Kongono\Tracing\Exception\TracingCollaboratorException;
use Kongono\Tracing\Exception\TracingException;
use Kongono\Tracing\Exception\TracingLifecycleException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class TracingExceptionTest extends TestCase
{
    public function testFamilyFactoriesAndNondisclosingPresentationAreExact(): void
    {
        $previous = new RuntimeException('secret previous message C:\\private\\trace.php');
        $exceptions = [
            InvalidTracingValueException::identifier(),
            InvalidTracingValueException::attributes(),
            InvalidTracingValueException::timing(),
            InvalidTracingValueException::status(),
            TracingLifecycleException::state(),
            TracingLifecycleException::ownership(),
            TracingLifecycleException::capacity(),
            TracingCollaboratorException::clock($previous),
            TracingCollaboratorException::identifierSource($previous),
            TracingCollaboratorException::sampler($previous),
            TraceRecordingException::failed($previous),
        ];

        foreach ($exceptions as $exception) {
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
            self::assertSame($exception->diagnosticId() . ': ' . $exception->diagnosticTitle(), (string) $exception);
            self::assertStringNotContainsString('secret previous message', $exception->getMessage());
            self::assertStringNotContainsString('secret previous message', (string) $exception);
            self::assertStringNotContainsString('C:\\private', (string) $exception);
        }
        self::assertSame($previous, $exceptions[7]->getPrevious());
        self::assertSame($previous, $exceptions[8]->getPrevious());
        self::assertSame($previous, $exceptions[9]->getPrevious());
        self::assertSame($previous, $exceptions[10]->getPrevious());
    }

    public function testCatchBoundaryAndConcreteFactorySurfacesAreExact(): void
    {
        $marker = new ReflectionClass(ExceptionInterface::class);
        $base = new ReflectionClass(TracingException::class);
        self::assertContains(\Throwable::class, $marker->getInterfaceNames());
        self::assertTrue($base->isAbstract());
        $parent = $base->getParentClass();
        self::assertNotFalse($parent);
        self::assertSame(RuntimeException::class, $parent->getName());

        $surfaces = [
            InvalidTracingValueException::class => ['attributes', 'identifier', 'status', 'timing'],
            TracingLifecycleException::class => ['capacity', 'ownership', 'state'],
            TracingCollaboratorException::class => ['clock', 'identifierSource', 'sampler'],
            TraceRecordingException::class => ['failed'],
        ];
        foreach ($surfaces as $class => $expected) {
            $reflection = new ReflectionClass($class);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->getConstructor()?->isPrivate());
            $methods = [];
            foreach ($reflection->getMethods() as $method) {
                if ($method->isPublic() && $method->getDeclaringClass()->getName() === $class) {
                    $methods[] = $method->getName();
                }
            }
            sort($methods);
            self::assertSame($expected, $methods);
        }
    }
}
