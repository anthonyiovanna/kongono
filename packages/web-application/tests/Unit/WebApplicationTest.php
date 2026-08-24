<?php

declare(strict_types=1);

namespace Kongono\Web\Application\Tests\Unit;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Lifecycle\OperationScope;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Web\Application\Exception\OperationCleanupException;
use Kongono\Web\Application\Exception\RetiredWebApplicationException;
use Kongono\Web\Application\WebApplication;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class WebApplicationTest extends TestCase
{
    public function testDirectTypedConstructionStoresPipelineAndBootsKernelOnce(): void
    {
        $response = self::createStub(ResponseInterface::class);
        $scope = new OperationScope();
        $request = $this->request('GET', '/');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturn($scope);
        $kernel->expects(self::once())->method('endOperation')->with(self::identicalTo($scope));
        $pipeline = $this->createMock(RequestHandlerInterface::class);
        $pipeline->expects(self::once())->method('handle')->with(self::identicalTo($request))->willReturn($response);

        $application = new WebApplication($kernel, $pipeline);

        self::assertSame($response, $application->handle($request));
    }

    public function testInvalidRouteHandlerKeyFailsBeforeKernelBoot(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::never())->method('boot');

        $this->expectException(InvalidHttpServerDefinitionException::class);
        WebApplication::boot(
            $kernel,
            new RouteTable([new RouteDefinition('route', ['GET'], '/')]),
            [],
            $this->handler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class)),
        );
    }

    public function testInvalidRouteHandlerValueFailsBeforeKernelBoot(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::never())->method('boot');

        $this->expectException(InvalidHttpServerDefinitionException::class);
        WebApplication::boot(
            $kernel,
            new RouteTable([new RouteDefinition('route', ['GET'], '/')]),
            ['route' => new \stdClass()],
            $this->handler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class)),
        );
    }

    public function testTooManyMiddlewareFailsBeforeKernelBoot(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::never())->method('boot');
        $middleware = array_fill(0, 129, self::createStub(MiddlewareInterface::class));

        $this->expectException(InvalidHttpServerDefinitionException::class);
        WebApplication::boot(
            $kernel,
            new RouteTable([]),
            [],
            $this->handler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class)),
            ...$middleware,
        );
    }

    public function testValidCompositionBootsOnceWithoutInvokingApplicationWork(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects(self::never())->method('process');

        $application = WebApplication::boot($kernel, new RouteTable([]), [], $handler, $middleware);

        self::assertFalse($application->isRetired());
    }

    public function testKernelBootFailurePropagatesByIdentity(): void
    {
        $failure = new RuntimeException('boot');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot')->willThrowException($failure);

        try {
            WebApplication::boot($kernel, new RouteTable([]), [], $this->unusedHandler());
            self::fail('Expected boot failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($failure, $actual);
        }
    }

    public function testDeclarationOrderedMiddlewareSurroundsSelectedRouteAndReturnsExactResponse(): void
    {
        $events = [];
        $scope = new OperationScope();
        $response = self::createStub(ResponseInterface::class);
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturnCallback(static function () use (&$events, $scope): OperationScope {
            $events[] = 'begin';

            return $scope;
        });
        $kernel->expects(self::once())->method('endOperation')->willReturnCallback(static function (OperationScope $actual) use (&$events, $scope): void {
            self::assertSame($scope, $actual);
            $events[] = 'end';
        });
        $selectedRequest = self::createStub(ServerRequestInterface::class);
        $handler = $this->handler(static function (ServerRequestInterface $request) use (&$events, $selectedRequest, $response): ResponseInterface {
            self::assertSame($selectedRequest, $request);
            $events[] = 'handler';

            return $response;
        });
        $first = $this->middleware(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use (&$events): ResponseInterface {
            $events[] = 'first in';
            $response = $next->handle($request);
            $events[] = 'first out';

            return $response;
        });
        $second = $this->middleware(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use (&$events): ResponseInterface {
            $events[] = 'second in';
            $response = $next->handle($request);
            $events[] = 'second out';

            return $response;
        });
        $application = WebApplication::boot(
            $kernel,
            new RouteTable([new RouteDefinition('route', ['GET'], '/')]),
            ['route' => $handler],
            $this->unusedHandler(),
            $first,
            $second,
        );

        self::assertSame($response, $application->handle($this->selectedRequest('GET', '/', $selectedRequest)));
        self::assertSame(['begin', 'first in', 'second in', 'handler', 'second out', 'first out', 'end'], $events);
    }

    public function testUnmatchedHandlingUsesExplicitHandlerInsideOneExactScope(): void
    {
        $scope = new OperationScope();
        $request = $this->request('GET', '/missing');
        $response = self::createStub(ResponseInterface::class);
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturn($scope);
        $kernel->expects(self::once())->method('endOperation')->with(self::identicalTo($scope));
        $unmatched = $this->handler(static function (ServerRequestInterface $actual) use ($request, $response): ResponseInterface {
            self::assertSame($request, $actual);

            return $response;
        });
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $unmatched);

        self::assertSame($response, $application->handle($request));
    }

    public function testDeliberateBeginLifecycleFailurePropagatesWithoutPipelineCleanupOrRetirement(): void
    {
        $failure = $this->beginLifecycleFailure();
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willThrowException($failure);
        $kernel->expects(self::never())->method('endOperation');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $handler);

        try {
            $application->handle($this->request('GET', '/'));
            self::fail('Expected begin lifecycle failure.');
        } catch (LifecycleException $actual) {
            self::assertSame($failure, $actual);
        }
        self::assertFalse($application->isRetired());
    }

    public function testUnknownBeginFailurePropagatesAndRetiresWithoutCleanup(): void
    {
        $failure = new RuntimeException('begin');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willThrowException($failure);
        $kernel->expects(self::never())->method('endOperation');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $handler);

        try {
            $application->handle($this->request('GET', '/'));
            self::fail('Expected begin failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($failure, $actual);
        }
        self::assertTrue($application->isRetired());
    }

    public function testPrimaryFailureWithSuccessfulCleanupPropagatesExactPrimaryWithoutRetirement(): void
    {
        $scope = new OperationScope();
        $failure = new RuntimeException('primary');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturn($scope);
        $kernel->expects(self::once())->method('endOperation')->with(self::identicalTo($scope));
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $this->handler(static function () use ($failure): ResponseInterface {
            throw $failure;
        }));

        try {
            $application->handle($this->request('GET', '/'));
            self::fail('Expected primary failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($failure, $actual);
        }
        self::assertFalse($application->isRetired());
    }

    public function testSuccessfulWorkWithCleanupFailureThrowsExactCleanupAndRetiresWithoutRetry(): void
    {
        $scope = new OperationScope();
        $response = self::createStub(ResponseInterface::class);
        $cleanup = new RuntimeException('cleanup');
        $endCalls = 0;
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturn($scope);
        $kernel->expects(self::once())->method('endOperation')->willReturnCallback(static function (OperationScope $actual) use ($scope, $cleanup, &$endCalls): void {
            self::assertSame($scope, $actual);
            ++$endCalls;
            throw $cleanup;
        });
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $this->handler(static fn(): ResponseInterface => $response));

        try {
            $application->handle($this->request('GET', '/'));
            self::fail('Expected cleanup failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($cleanup, $actual);
        }
        self::assertSame(1, $endCalls);
        self::assertTrue($application->isRetired());
    }

    public function testSimultaneousPrimaryAndCleanupFailurePreservesBothAndRetires(): void
    {
        $scope = new OperationScope();
        $primary = new RuntimeException('primary');
        $cleanup = new \LogicException('cleanup');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturn($scope);
        $kernel->expects(self::once())->method('endOperation')->with(self::identicalTo($scope))->willThrowException($cleanup);
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $this->handler(static function () use ($primary): ResponseInterface {
            throw $primary;
        }));

        try {
            $application->handle($this->request('GET', '/'));
            self::fail('Expected composite failure.');
        } catch (OperationCleanupException $actual) {
            self::assertSame($primary, $actual->primaryFailure());
            self::assertSame($cleanup, $actual->cleanupFailure());
            self::assertSame($primary, $actual->getPrevious());
        }
        self::assertTrue($application->isRetired());
    }

    public function testRetiredHandlingAndShutdownRejectBeforeDependencyAccess(): void
    {
        $beginCalls = 0;
        $shutdownCalls = 0;
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->method('beginOperation')->willReturnCallback(static function () use (&$beginCalls): OperationScope {
            ++$beginCalls;
            throw new RuntimeException('uncertain begin');
        });
        $kernel->method('shutdown')->willReturnCallback(static function () use (&$shutdownCalls): void {
            ++$shutdownCalls;
        });
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $this->unusedHandler());
        try {
            $application->handle($this->request('GET', '/'));
        } catch (RuntimeException) {
        }

        foreach (['handle', 'shutdown'] as $operation) {
            try {
                if ('handle' === $operation) {
                    $application->handle($this->request('GET', '/later'));
                } else {
                    $application->shutdown();
                }
                self::fail('Expected retired refusal.');
            } catch (RetiredWebApplicationException $failure) {
                self::assertSame('KGN-WEB-APPLICATION-1001', $failure->diagnosticId());
            }
        }
        self::assertSame(1, $beginCalls);
        self::assertSame(0, $shutdownCalls);
    }

    public function testSuccessfulShutdownDelegatesExactlyOnce(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('shutdown');
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $this->unusedHandler());

        $application->shutdown();
        self::assertFalse($application->isRetired());
    }

    public function testDeliberateShutdownLifecycleFailureDoesNotRetire(): void
    {
        $failure = $this->shutdownLifecycleFailure();
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('shutdown')->willThrowException($failure);
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $this->unusedHandler());

        try {
            $application->shutdown();
            self::fail('Expected shutdown lifecycle failure.');
        } catch (LifecycleException $actual) {
            self::assertSame($failure, $actual);
        }
        self::assertFalse($application->isRetired());
    }

    public function testUnknownShutdownFailurePropagatesAndRetires(): void
    {
        $failure = new RuntimeException('shutdown');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('shutdown')->willThrowException($failure);
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $this->unusedHandler());

        try {
            $application->shutdown();
            self::fail('Expected shutdown failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($failure, $actual);
        }
        self::assertTrue($application->isRetired());
    }

    public function testSequentialCallsUseDistinctScopesRequestsAndResponses(): void
    {
        $firstScope = new OperationScope();
        $secondScope = new OperationScope();
        $scopes = [$firstScope, $secondScope];
        $ended = [];
        $firstRequest = $this->request('GET', '/one');
        $secondRequest = $this->request('GET', '/two');
        $firstResponse = self::createStub(ResponseInterface::class);
        $secondResponse = self::createStub(ResponseInterface::class);
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::exactly(2))->method('beginOperation')->willReturnCallback(static function () use (&$scopes): OperationScope {
            $scope = array_shift($scopes);
            self::assertInstanceOf(OperationScope::class, $scope);

            return $scope;
        });
        $kernel->expects(self::exactly(2))->method('endOperation')->willReturnCallback(static function (OperationScope $scope) use (&$ended): void {
            $ended[] = $scope;
        });
        $handler = $this->handler(static function (ServerRequestInterface $request) use ($firstRequest, $firstResponse, $secondResponse): ResponseInterface {
            return $request === $firstRequest ? $firstResponse : $secondResponse;
        });
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $handler);

        self::assertSame($firstResponse, $application->handle($firstRequest));
        self::assertSame($secondResponse, $application->handle($secondRequest));
        self::assertSame([$firstScope, $secondScope], $ended);
        self::assertNotSame($firstRequest, $secondRequest);
        self::assertNotSame($firstResponse, $secondResponse);
    }

    public function testReentrantCallsCloseInnerBeforeOuterWithoutAmbientScope(): void
    {
        $outerScope = new OperationScope();
        $innerScope = new OperationScope();
        $scopes = [$outerScope, $innerScope];
        $events = [];
        $outerRequest = $this->request('GET', '/outer');
        $innerRequest = $this->request('GET', '/inner');
        $outerResponse = self::createStub(ResponseInterface::class);
        $innerResponse = self::createStub(ResponseInterface::class);
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::exactly(2))->method('beginOperation')->willReturnCallback(static function () use (&$scopes, &$events): OperationScope {
            $scope = array_shift($scopes);
            self::assertInstanceOf(OperationScope::class, $scope);
            $events[] = $scope;

            return $scope;
        });
        $kernel->expects(self::exactly(2))->method('endOperation')->willReturnCallback(static function (OperationScope $scope) use (&$events): void {
            $events[] = $scope;
        });
        $application = null;
        $handler = $this->handler(static function (ServerRequestInterface $request) use (&$application, &$events, $outerRequest, $innerRequest, $outerResponse, $innerResponse): ResponseInterface {
            $events[] = $request;
            if ($request === $outerRequest) {
                self::assertInstanceOf(WebApplication::class, $application);
                self::assertSame($innerResponse, $application->handle($innerRequest));

                return $outerResponse;
            }

            self::assertSame($innerRequest, $request);

            return $innerResponse;
        });
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $handler);

        self::assertSame($outerResponse, $application->handle($outerRequest));
        self::assertSame([$outerScope, $outerRequest, $innerScope, $innerRequest, $innerScope, $outerScope], $events);
    }

    public function testClassShapeAndPublicContractAreExact(): void
    {
        $reflection = new \ReflectionClass(WebApplication::class);
        self::assertTrue($reflection->isFinal());
        self::assertFalse($reflection->isReadOnly());
        self::assertFalse($reflection->getParentClass());
        self::assertSame([RequestHandlerInterface::class], $reflection->getInterfaceNames());
        self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);

        $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => WebApplication::class === $property->getDeclaringClass()->getName()));
        usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
        self::assertSame(['kernel', 'pipeline', 'retired'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
        }
        self::assertTrue((new \ReflectionProperty(WebApplication::class, 'kernel'))->isReadOnly());
        self::assertTrue((new \ReflectionProperty(WebApplication::class, 'pipeline'))->isReadOnly());
        self::assertFalse((new \ReflectionProperty(WebApplication::class, 'retired'))->isReadOnly());

        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPublic());
        self::assertSame([KernelInterface::class, RequestHandlerInterface::class], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $constructor->getParameters()));
        self::assertTrue($reflection->getMethod('__clone')->isPrivate());
        self::assertFalse($reflection->hasMethod('__destruct'));
        self::assertFalse($reflection->hasMethod('__serialize'));
        self::assertFalse($reflection->hasMethod('__unserialize'));

        $public = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => WebApplication::class === $method->getDeclaringClass()->getName()),
        );
        sort($public);
        self::assertSame(['__construct', 'boot', 'handle', 'isRetired', 'shutdown'], $public);
    }

    public function testDestructionDoesNotTriggerAutomaticShutdown(): void
    {
        $shutdownCalls = 0;
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->method('shutdown')->willReturnCallback(static function () use (&$shutdownCalls): void {
            ++$shutdownCalls;
        });
        $application = WebApplication::boot($kernel, new RouteTable([]), [], $this->unusedHandler());

        unset($application);
        gc_collect_cycles();
        self::assertSame(0, $shutdownCalls);
    }

    private function handler(Closure $callback): RequestHandlerInterface
    {
        return new readonly class ($callback) implements RequestHandlerInterface {
            public function __construct(private Closure $callback) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = ($this->callback)($request);
                if (!$response instanceof ResponseInterface) {
                    throw new RuntimeException('Test handler callback must return ResponseInterface.');
                }

                return $response;
            }
        };
    }

    private function middleware(Closure $callback): MiddlewareInterface
    {
        return new readonly class ($callback) implements MiddlewareInterface {
            public function __construct(private Closure $callback) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = ($this->callback)($request, $handler);
                if (!$response instanceof ResponseInterface) {
                    throw new RuntimeException('Test middleware callback must return ResponseInterface.');
                }

                return $response;
            }
        };
    }

    private function unusedHandler(): RequestHandlerInterface
    {
        return $this->handler(static function (): ResponseInterface {
            self::fail('Handler must not be called.');
        });
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

    private function selectedRequest(string $method, string $path, ServerRequestInterface $derived): ServerRequestInterface
    {
        $uri = self::createStub(UriInterface::class);
        $uri->method('getPath')->willReturn($path);
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getUri')->willReturn($uri);
        $request->method('withAttribute')->willReturn($derived);

        return $request;
    }

    private function beginLifecycleFailure(): LifecycleException
    {
        return new LifecycleException(
            LifecycleDiagnostic::BeginOperationWhenNotBooted,
            'begin rejected',
            ['actual_state' => 'Unbooted', 'expected_state' => 'Booted'],
        );
    }

    private function shutdownLifecycleFailure(): LifecycleException
    {
        return new LifecycleException(
            LifecycleDiagnostic::ShutdownWhenNotBooted,
            'shutdown rejected',
            ['actual_state' => 'Shutdown', 'expected_state' => 'Booted'],
        );
    }
}
