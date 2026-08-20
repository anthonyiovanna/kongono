<?php

declare(strict_types=1);

namespace Kongono\Routing\Tests\Unit\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\ExceptionInterface;
use Kongono\Routing\Exception\InvalidRouteDefinitionException;
use Kongono\Routing\Exception\InvalidRouteRequestException;
use Kongono\Routing\Match\RouteMatch;
use Kongono\Routing\Matcher\RouteMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Throwable;

final class RoutingExceptionTest extends TestCase
{
    public function testExceptionsPreserveNativeCategoryDiagnosticBoundaryAndPrevious(): void
    {
        $previous = new RuntimeException('cause');
        $definitionException = new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteName, ['name_length' => 0, 'violation' => 'empty'], $previous);
        $requestException = new InvalidRouteRequestException(RoutingDiagnostic::InvalidRouteRequest, ['component' => 'method', 'actual_length' => 0, 'violation' => 'empty'], $previous);

        self::assertInstanceOf(InvalidArgumentException::class, $definitionException);
        $definitionParent = (new \ReflectionClass($definitionException))->getParentClass();
        self::assertNotFalse($definitionParent);
        self::assertSame(InvalidArgumentException::class, $definitionParent->getName());
        self::assertInstanceOf(RuntimeException::class, $requestException);
        $requestParent = (new \ReflectionClass($requestException))->getParentClass();
        self::assertNotFalse($requestParent);
        self::assertSame(RuntimeException::class, $requestParent->getName());

        foreach ([$definitionException, $requestException] as $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($exception->diagnosticTitle(), $exception->getMessage());
            self::assertSame(0, $exception->getCode());
            self::assertSame($previous, $exception->getPrevious());
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
        }
    }

    public function testMarkerAndConcreteShapesAndStatusesAreExact(): void
    {
        $marker = new \ReflectionClass(ExceptionInterface::class);
        self::assertTrue($marker->implementsInterface(Throwable::class));
        self::assertSame(ApiStatus::Public, $marker->getAttributes(Api::class)[0]->newInstance()->status);
        $markerMethods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($marker->getMethods(), static fn(\ReflectionMethod $method): bool => ExceptionInterface::class === $method->getDeclaringClass()->getName()),
        );
        sort($markerMethods);
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $markerMethods);

        foreach ([InvalidRouteDefinitionException::class, InvalidRouteRequestException::class] as $class) {
            $reflection = new \ReflectionClass($class);
            self::assertTrue($reflection->isFinal());
            self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
            $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $class === $property->getDeclaringClass()->getName()));
            self::assertSame(['diagnostic', 'diagnosticContext'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
            foreach ($properties as $property) {
                self::assertTrue($property->isPrivate());
                self::assertTrue($property->isReadOnly());
            }
            self::assertCount(3, $reflection->getConstructor()?->getParameters() ?? []);
            self::assertNotSame($class, $reflection->getMethod('__toString')->getDeclaringClass()->getName());
        }
    }

    public function testRejectedRawValuesDoNotEnterDiagnosticSurfaces(): void
    {
        $invalidName = 'rejected route name sentinel';
        $this->assertRawValueExcluded($invalidName, static function () use ($invalidName): void {
            new RouteDefinition($invalidName, ['GET'], '/');
        });

        $invalidMethod = 'REJECTED METHOD SENTINEL';
        $this->assertRawValueExcluded($invalidMethod, static function () use ($invalidMethod): void {
            new RouteDefinition('route', [$invalidMethod], '/');
        });

        $invalidTemplate = '/rejected template sentinel';
        $this->assertRawValueExcluded($invalidTemplate, static function () use ($invalidTemplate): void {
            new RouteDefinition('route', ['GET'], $invalidTemplate);
        });

        $invalidParameter = 'rejected match sentinel';
        $this->assertRawValueExcluded($invalidParameter, static function () use ($invalidParameter): void {
            new RouteMatch(new RouteDefinition('route', ['GET'], '/{id}'), ['id' => $invalidParameter]);
        });

        $invalidRequestMethod = 'REJECTED REQUEST METHOD SENTINEL';
        $this->assertRawValueExcluded($invalidRequestMethod, function () use ($invalidRequestMethod): void {
            (new RouteMatcher(new RouteTable([])))->match($this->request($invalidRequestMethod, '/'));
        });

        $invalidRequestPath = '/rejected request path sentinel';
        $this->assertRawValueExcluded($invalidRequestPath, function () use ($invalidRequestPath): void {
            (new RouteMatcher(new RouteTable([])))->match($this->request('GET', $invalidRequestPath));
        });
    }

    /** @param callable(): void $operation */
    private function assertRawValueExcluded(string $raw, callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected routing failure.');
        } catch (ExceptionInterface $exception) {
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

    private function request(string $method, string $path): ServerRequestInterface
    {
        $uri = self::createStub(UriInterface::class);
        $uri->method('getPath')->willReturn($path);
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getUri')->willReturn($uri);

        return $request;
    }
}
