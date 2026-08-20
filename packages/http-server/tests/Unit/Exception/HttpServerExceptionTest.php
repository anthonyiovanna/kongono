<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Tests\Unit\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticPresentation\Exception\ExceptionInterface as DiagnosticPresentationExceptionInterface;
use Kongono\Diagnostics\Exception\ExceptionInterface as DiagnosticsExceptionInterface;
use Kongono\Http\Message\Exception\ExceptionInterface as HttpMessageExceptionInterface;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Kongono\Http\Server\Exception\ExceptionInterface;
use Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException;
use Kongono\Http\Server\MiddlewarePipeline;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Http\Server\Tests\Fixture\CallbackRequestHandler;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

final class HttpServerExceptionTest extends TestCase
{
    public function testExceptionContractStatusPreviousAndMarkerSeparationAreExact(): void
    {
        $previous = new RuntimeException('cause');
        $exception = new InvalidHttpServerDefinitionException(HttpServerDiagnostic::InvalidRouteHandlerValue, [
            'handler_position' => 0,
            'violation' => 'not_request_handler',
        ], $previous);

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame('KGN-HTTP-SERVER-1003', $exception->diagnosticId());
        self::assertSame($exception->diagnosticTitle(), $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());

        foreach ([KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, DiagnosticPresentationExceptionInterface::class, DiagnosticsExceptionInterface::class, HttpMessageExceptionInterface::class, RoutingExceptionInterface::class] as $marker) {
            self::assertFalse(is_a(ExceptionInterface::class, $marker, true));
            self::assertFalse(is_a($marker, ExceptionInterface::class, true));
        }

        $marker = new \ReflectionClass(ExceptionInterface::class);
        self::assertSame(ApiStatus::Public, $marker->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame([Throwable::class, \Stringable::class], $marker->getInterfaceNames());
        $markerMethods = array_map(static fn(\ReflectionMethod $method): string => $method->getName(), array_filter($marker->getMethods(), static fn(\ReflectionMethod $method): bool => ExceptionInterface::class === $method->getDeclaringClass()->getName()));
        sort($markerMethods);
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $markerMethods);

        $reflection = new \ReflectionClass(InvalidHttpServerDefinitionException::class);
        self::assertTrue($reflection->isFinal());
        self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        $parent = $reflection->getParentClass();
        self::assertNotFalse($parent);
        self::assertSame(InvalidArgumentException::class, $parent->getName());
        $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => InvalidHttpServerDefinitionException::class === $property->getDeclaringClass()->getName()));
        usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
        self::assertSame(['diagnostic', 'diagnosticContext'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
        }
        self::assertSame([HttpServerDiagnostic::class, 'array', '?Throwable'], array_map(static fn($parameter): string => (string) $parameter->getType(), $reflection->getConstructor()?->getParameters() ?? []));
        self::assertNotSame(InvalidHttpServerDefinitionException::class, $reflection->getMethod('__toString')->getDeclaringClass()->getName());
    }

    public function testRejectedCompositionValuesDoNotEnterPackageOwnedDiagnosticSurfaces(): void
    {
        $terminal = $this->handler();
        try {
            new MiddlewarePipeline($terminal, ...array_fill(0, 129, self::createStub(\Psr\Http\Server\MiddlewareInterface::class)));
            self::fail('Expected middleware bound failure.');
        } catch (ExceptionInterface $exception) {
            $this->assertSurfaceExcludes($exception, 'CallbackMiddleware');
        }

        $rawKey = 'raw_route_key_sentinel';
        try {
            new RoutingRequestHandler(new RouteTable([new RouteDefinition('expected', ['GET'], '/')]), [$rawKey => $terminal], $terminal);
            self::fail('Expected handler-key failure.');
        } catch (ExceptionInterface $exception) {
            $this->assertSurfaceExcludes($exception, $rawKey);
        }

        $rawClass = RuntimeException::class;
        try {
            new RoutingRequestHandler(new RouteTable([new RouteDefinition('expected', ['GET'], '/')]), ['expected' => new RuntimeException('raw handler sentinel')], $terminal);
            self::fail('Expected handler-value failure.');
        } catch (ExceptionInterface $exception) {
            $this->assertSurfaceExcludes($exception, $rawClass);
            $this->assertSurfaceExcludes($exception, 'raw handler sentinel');
        }
    }

    private function handler(): CallbackRequestHandler
    {
        return new CallbackRequestHandler(static fn(ServerRequestInterface $request): ResponseInterface => self::createStub(ResponseInterface::class));
    }

    private function assertSurfaceExcludes(ExceptionInterface $exception, string $raw): void
    {
        $surface = implode('|', [
            $exception->getMessage(),
            $exception->diagnosticId(),
            $exception->diagnosticTitle(),
            $exception->diagnosticRemediation(),
            $exception->diagnosticContext()->operation(),
            $exception->diagnosticContext()->violatedInvariant(),
            ...array_map(static fn($field): string => (string) $field->value(), $exception->diagnosticContext()->fields()),
        ]);
        self::assertStringNotContainsString($raw, $surface);
    }
}
