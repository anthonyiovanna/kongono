<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Tests\Unit;

use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\DiagnosticPresentationException;
use Kongono\DiagnosticPresentation\Exception\ExceptionInterface;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;
use Kongono\Diagnostics\Exception\ExceptionInterface as DiagnosticsExceptionInterface;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Throwable;

final class ExceptionBoundaryTest extends TestCase
{
    public function testMarkerShapeAndSeparationAreExact(): void
    {
        $reflection = new ReflectionClass(ExceptionInterface::class);
        $methods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(ReflectionMethod $method): bool => ExceptionInterface::class === $method->getDeclaringClass()->getName(),
        ));
        $names = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($names);

        self::assertSame([Throwable::class, \Stringable::class], $reflection->getInterfaceNames());
        self::assertSame(['diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $names);
        self::assertFalse($reflection->hasMethod('diagnosticContext'));
        foreach ([KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, DiagnosticsExceptionInterface::class] as $marker) {
            self::assertFalse(is_a(ExceptionInterface::class, $marker, true));
            self::assertFalse(is_a($marker, ExceptionInterface::class, true));
        }
    }

    public function testBaseShapeDelegationPreviousAndCodeAreExact(): void
    {
        $reflection = new ReflectionClass(DiagnosticPresentationException::class);
        $properties = array_values(array_filter(
            $reflection->getProperties(),
            static fn(\ReflectionProperty $property): bool => DiagnosticPresentationException::class === $property->getDeclaringClass()->getName(),
        ));
        $methods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(ReflectionMethod $method): bool => DiagnosticPresentationException::class === $method->getDeclaringClass()->getName(),
        ));
        $previous = new RuntimeException('native');
        $failure = new InvalidDiagnosticProjectionException(
            DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle,
            'Structural title failure.',
            $previous,
        );
        $parent = $reflection->getParentClass();

        if (false === $parent) {
            self::fail('Diagnostic Presentation base must extend RuntimeException.');
        }

        self::assertTrue($reflection->isAbstract());
        self::assertSame(RuntimeException::class, $parent->getName());
        self::assertCount(1, $properties);
        self::assertSame('diagnostic', $properties[0]->getName());
        self::assertSame(DiagnosticPresentationDiagnostic::class, (string) $properties[0]->getType());
        self::assertTrue($properties[0]->isPrivate());
        self::assertTrue($properties[0]->isReadOnly());
        foreach ($methods as $method) {
            self::assertTrue($method->isFinal());
            self::assertTrue($method->isPublic());
        }
        self::assertSame(DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle->value, $failure->diagnosticId());
        self::assertSame(DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle->title(), $failure->diagnosticTitle());
        self::assertSame(DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle->remediation(), $failure->diagnosticRemediation());
        self::assertSame('Structural title failure.', $failure->getMessage());
        self::assertSame(0, $failure->getCode());
        self::assertSame($previous, $failure->getPrevious());
        self::assertFalse($reflection->hasMethod('diagnosticContext'));
    }

    public function testConcreteFailureIsFinalEmptyAndOnlyInPresentationFamily(): void
    {
        $reflection = new ReflectionClass(InvalidDiagnosticProjectionException::class);
        $parent = $reflection->getParentClass();

        if (false === $parent) {
            self::fail('Invalid projection failure must extend the package base.');
        }

        self::assertTrue($reflection->isFinal());
        self::assertSame(DiagnosticPresentationException::class, $parent->getName());
        self::assertSame([], array_values(array_filter(
            $reflection->getMethods(),
            static fn(ReflectionMethod $method): bool => InvalidDiagnosticProjectionException::class === $method->getDeclaringClass()->getName(),
        )));
        self::assertSame([], array_values(array_filter(
            $reflection->getProperties(),
            static fn(\ReflectionProperty $property): bool => InvalidDiagnosticProjectionException::class === $property->getDeclaringClass()->getName(),
        )));
        $failure = new InvalidDiagnosticProjectionException(DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle, 'Failure.');
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        $interfaces = $reflection->getInterfaceNames();
        self::assertNotContains(KernelExceptionInterface::class, $interfaces);
        self::assertNotContains(GraphExceptionInterface::class, $interfaces);
        self::assertNotContains(DependencyInjectionExceptionInterface::class, $interfaces);
        self::assertNotContains(DiagnosticsExceptionInterface::class, $interfaces);
    }
}
