<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Tests\Unit\Exception;

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
use Kongono\Web\Application\Exception\ExceptionInterface as WebApplicationExceptionInterface;
use Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic;
use Kongono\Web\Runtime\Exception\DeliveryCleanupException;
use Kongono\Web\Runtime\Exception\ExceptionInterface;
use Kongono\Web\Runtime\Exception\InvalidGeneratedWebRuntimeException;
use Kongono\Web\Runtime\Exception\RetiredWebRuntimeException;
use Kongono\Web\Runtime\Exception\WebRuntimeException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class WebRuntimeExceptionTest extends TestCase
{
    public function testMarkerBaseAndConcreteShapesAreExactAndSeparated(): void
    {
        $marker = new \ReflectionClass(ExceptionInterface::class);
        self::assertSame(ApiStatus::Public, $marker->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame([Throwable::class, \Stringable::class], $marker->getInterfaceNames());
        self::assertSame(
            ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            $this->declaredPublicMethodNames($marker),
        );
        foreach ([KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, DiagnosticPresentationExceptionInterface::class, DiagnosticsExceptionInterface::class, HttpMessageExceptionInterface::class, HttpServerExceptionInterface::class, RoutingExceptionInterface::class, WebApplicationExceptionInterface::class] as $otherMarker) {
            self::assertFalse(is_a(ExceptionInterface::class, $otherMarker, true));
            self::assertFalse(is_a($otherMarker, ExceptionInterface::class, true));
        }

        $base = new \ReflectionClass(WebRuntimeException::class);
        self::assertTrue($base->isAbstract());
        $parent = $base->getParentClass();
        self::assertInstanceOf(\ReflectionClass::class, $parent);
        self::assertSame(RuntimeException::class, $parent->getName());
        self::assertSame(ApiStatus::Internal, $base->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame(['diagnostic', 'diagnosticContext'], $this->declaredPropertyNames($base));
        $constructor = $base->getConstructor();
        self::assertNotNull($constructor);
        self::assertTrue($constructor->isProtected());
        self::assertSame([WebRuntimeDiagnostic::class, '?Throwable', '?string'], array_map(
            static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $constructor->getParameters(),
        ));
        foreach (['diagnosticContext', 'diagnosticId', 'diagnosticTitle', 'diagnosticRemediation'] as $method) {
            self::assertTrue($base->getMethod($method)->isFinal());
        }
        self::assertSame(WebRuntimeException::class, $base->getMethod('__toString')->getDeclaringClass()->getName());

        $retired = new \ReflectionClass(RetiredWebRuntimeException::class);
        self::assertTrue($retired->isFinal());
        self::assertSame(ApiStatus::Experimental, $retired->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame([], $this->declaredPropertyNames($retired));
        self::assertSame(['__construct'], $this->declaredPublicMethodNames($retired));
        self::assertSame([], $retired->getConstructor()?->getParameters());

        $cleanup = new \ReflectionClass(DeliveryCleanupException::class);
        self::assertTrue($cleanup->isFinal());
        self::assertSame(ApiStatus::Experimental, $cleanup->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame(['cleanupFailure', 'primaryFailure'], $this->declaredPropertyNames($cleanup));
        self::assertSame(['__construct', 'cleanupFailure', 'primaryFailure'], $this->declaredPublicMethodNames($cleanup));
        self::assertSame(['Throwable', 'Throwable'], array_map(
            static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $cleanup->getConstructor()?->getParameters() ?? [],
        ));
    }

    public function testRetiredFailureHasExactSafeDiagnosticAndNoPrevious(): void
    {
        $exception = new RetiredWebRuntimeException();
        self::assertSame('KGN-WEB-RUNTIME-1001', $exception->diagnosticId());
        self::assertSame('Web runtime adapter is retired', $exception->getMessage());
        self::assertSame($exception->diagnosticTitle(), $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
        self::assertSame([], $exception->diagnosticContext()->fields());
    }

    public function testCompositePreservesExactFailuresWithoutRawDiagnosticDisclosure(): void
    {
        $primary = new RuntimeException('raw-primary-request-delivery-sentinel');
        $cleanup = new \LogicException('raw-cleanup-trace-sentinel');
        $exception = new DeliveryCleanupException($primary, $cleanup);

        self::assertSame($primary, $exception->primaryFailure());
        self::assertSame($cleanup, $exception->cleanupFailure());
        self::assertSame($primary, $exception->getPrevious());
        self::assertSame('KGN-WEB-RUNTIME-1002', $exception->diagnosticId());
        self::assertSame([], $exception->diagnosticContext()->fields());

        $surface = implode('|', [
            $exception->getMessage(),
            $exception->diagnosticId(),
            $exception->diagnosticTitle(),
            $exception->diagnosticRemediation(),
            $exception->diagnosticContext()->operation(),
            $exception->diagnosticContext()->violatedInvariant(),
        ]);
        foreach (['raw-primary-request-delivery-sentinel', 'raw-cleanup-trace-sentinel', RuntimeException::class, \LogicException::class] as $raw) {
            self::assertStringNotContainsString($raw, $surface);
        }
        self::assertNotSame(DeliveryCleanupException::class, (new \ReflectionClass(DeliveryCleanupException::class))->getMethod('__toString')->getDeclaringClass()->getName());
    }

    public function testGeneratedIntegrationFailureSanitizesDirectInputAndStringification(): void
    {
        $secret = 'raw-generated-secret-sentinel';
        $exception = new InvalidGeneratedWebRuntimeException($secret);

        self::assertSame('KGN-WEB-RUNTIME-1003', $exception->diagnosticId());
        self::assertSame('application_root_shape', $exception->diagnosticContext()->fields()[0]->value());
        self::assertSame('KGN-WEB-RUNTIME-1003: Invalid generated web-runtime integration', (string) $exception);
        self::assertStringNotContainsString($secret, (string) $exception);
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPropertyNames(\ReflectionClass $reflection): array
    {
        $properties = array_values(array_filter(
            $reflection->getProperties(),
            static fn(\ReflectionProperty $property): bool => $reflection->getName() === $property->getDeclaringClass()->getName(),
        ));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }
        $names = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties);
        sort($names);

        return $names;
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethodNames(\ReflectionClass $reflection): array
    {
        $names = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName()),
        );
        sort($names);

        return $names;
    }
}
