<?php

declare(strict_types=1);

namespace Kongono\Web\Application\Tests\Unit\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticPresentation\Exception\ExceptionInterface as DiagnosticPresentationExceptionInterface;
use Kongono\Diagnostics\Exception\ExceptionInterface as DiagnosticsExceptionInterface;
use Kongono\Http\Message\Exception\ExceptionInterface as HttpMessageExceptionInterface;
use Kongono\Http\Server\Exception\ExceptionInterface as HttpServerExceptionInterface;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Kongono\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Kongono\Web\Application\Exception\ExceptionInterface;
use Kongono\Web\Application\Exception\OperationCleanupException;
use Kongono\Web\Application\Exception\RetiredWebApplicationException;
use Kongono\Web\Application\Exception\WebApplicationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class WebApplicationExceptionTest extends TestCase
{
    public function testMarkerBaseAndConcreteShapesAreExact(): void
    {
        $marker = new \ReflectionClass(ExceptionInterface::class);
        self::assertSame(ApiStatus::Public, $marker->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame([Throwable::class, \Stringable::class], $marker->getInterfaceNames());
        $markerMethods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($marker->getMethods(), static fn(\ReflectionMethod $method): bool => ExceptionInterface::class === $method->getDeclaringClass()->getName()),
        );
        sort($markerMethods);
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $markerMethods);

        foreach ([KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, DiagnosticsExceptionInterface::class, DiagnosticPresentationExceptionInterface::class, HttpMessageExceptionInterface::class, RoutingExceptionInterface::class, HttpServerExceptionInterface::class] as $otherMarker) {
            self::assertFalse(is_a(ExceptionInterface::class, $otherMarker, true));
            self::assertFalse(is_a($otherMarker, ExceptionInterface::class, true));
        }

        $base = new \ReflectionClass(WebApplicationException::class);
        self::assertTrue($base->isAbstract());
        $baseParent = $base->getParentClass();
        self::assertNotFalse($baseParent);
        self::assertSame(RuntimeException::class, $baseParent->getName());
        self::assertSame(ApiStatus::Internal, $base->getAttributes(Api::class)[0]->newInstance()->status);
        $baseProperties = array_values(array_filter($base->getProperties(), static fn(\ReflectionProperty $property): bool => WebApplicationException::class === $property->getDeclaringClass()->getName()));
        usort($baseProperties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
        self::assertSame(['diagnostic', 'diagnosticContext'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $baseProperties));
        foreach ($baseProperties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
        }
        self::assertSame(['Kongono\\Web\\Application\\Diagnostic\\WebApplicationDiagnostic', '?Throwable'], array_map(
            static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $base->getConstructor()?->getParameters() ?? [],
        ));
        self::assertTrue($base->getConstructor()?->isProtected());
        foreach (['diagnosticContext', 'diagnosticId', 'diagnosticTitle', 'diagnosticRemediation'] as $method) {
            self::assertTrue($base->getMethod($method)->isFinal());
        }
        self::assertNotSame(WebApplicationException::class, $base->getMethod('__toString')->getDeclaringClass()->getName());

        $retired = new \ReflectionClass(RetiredWebApplicationException::class);
        self::assertTrue($retired->isFinal());
        self::assertSame(ApiStatus::Experimental, $retired->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame([], array_values(array_filter($retired->getProperties(), static fn(\ReflectionProperty $property): bool => RetiredWebApplicationException::class === $property->getDeclaringClass()->getName())));
        self::assertSame([], $retired->getConstructor()?->getParameters());

        $cleanup = new \ReflectionClass(OperationCleanupException::class);
        self::assertTrue($cleanup->isFinal());
        self::assertSame(ApiStatus::Experimental, $cleanup->getAttributes(Api::class)[0]->newInstance()->status);
        $cleanupProperties = array_values(array_filter($cleanup->getProperties(), static fn(\ReflectionProperty $property): bool => OperationCleanupException::class === $property->getDeclaringClass()->getName()));
        usort($cleanupProperties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
        self::assertSame(['cleanupFailure', 'primaryFailure'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $cleanupProperties));
        foreach ($cleanupProperties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
        }
        $declaredMethods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($cleanup->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => OperationCleanupException::class === $method->getDeclaringClass()->getName()),
        );
        sort($declaredMethods);
        self::assertSame(['__construct', 'cleanupFailure', 'primaryFailure'], $declaredMethods);
    }

    public function testRetiredFailureHasExactSafeDiagnosticAndNoPrevious(): void
    {
        $exception = new RetiredWebApplicationException();
        self::assertSame('KGN-WEB-APPLICATION-1001', $exception->diagnosticId());
        self::assertSame('Web application is retired', $exception->getMessage());
        self::assertSame($exception->diagnosticTitle(), $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
        self::assertSame([], $exception->diagnosticContext()->fields());
    }

    public function testCompositePreservesBothFailureIdentitiesWithoutRawDiagnosticData(): void
    {
        $primary = new RuntimeException('raw-primary-sentinel');
        $cleanup = new \LogicException('raw-cleanup-sentinel');
        $exception = new OperationCleanupException($primary, $cleanup);

        self::assertSame($primary, $exception->primaryFailure());
        self::assertSame($cleanup, $exception->cleanupFailure());
        self::assertSame($primary, $exception->getPrevious());
        self::assertSame('KGN-WEB-APPLICATION-1002', $exception->diagnosticId());
        self::assertSame([], $exception->diagnosticContext()->fields());

        $surface = implode('|', [
            $exception->getMessage(),
            $exception->diagnosticId(),
            $exception->diagnosticTitle(),
            $exception->diagnosticRemediation(),
            $exception->diagnosticContext()->operation(),
            $exception->diagnosticContext()->violatedInvariant(),
        ]);
        foreach (['raw-primary-sentinel', 'raw-cleanup-sentinel', RuntimeException::class, \LogicException::class] as $raw) {
            self::assertStringNotContainsString($raw, $surface);
        }

        $properties = array_map(
            static fn(\ReflectionProperty $property): string => $property->getName(),
            (new \ReflectionClass(OperationCleanupException::class))->getProperties(),
        );
        foreach (['request', 'response', 'handler', 'middleware', 'route', 'scope', 'contextValues'] as $forbidden) {
            self::assertNotContains($forbidden, $properties);
        }
        self::assertNotSame(OperationCleanupException::class, (new \ReflectionClass(OperationCleanupException::class))->getMethod('__toString')->getDeclaringClass()->getName());
    }
}
