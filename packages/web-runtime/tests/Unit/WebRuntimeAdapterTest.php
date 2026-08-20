<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Tests\Unit;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Lifecycle\OperationScope;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use Kongono\Web\Runtime\Exception\DeliveryCleanupException;
use Kongono\Web\Runtime\Exception\RetiredWebRuntimeException;
use Kongono\Web\Runtime\Tests\Fixture\ExplosiveKernelDiagnosticException;
use Kongono\Web\Runtime\WebRuntimeAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class WebRuntimeAdapterTest extends TestCase
{
    public function testClassAndDeliveryPortShapesAreExact(): void
    {
        $delivery = new \ReflectionClass(WebDeliveryInterface::class);
        self::assertTrue($delivery->isInterface());
        self::assertSame([], $delivery->getInterfaceNames());
        self::assertSame(ApiStatus::Public, $delivery->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame(['close', 'evaluateTrustedPresentationPermission', 'request', 'send'], $this->declaredPublicMethodNames($delivery));
        self::assertSame([], $delivery->getConstants());
        self::assertSame([], $delivery->getProperties());

        $reflection = new \ReflectionClass(WebRuntimeAdapter::class);
        self::assertTrue($reflection->isFinal());
        self::assertFalse($reflection->isReadOnly());
        self::assertFalse($reflection->getParentClass());
        self::assertSame([], $reflection->getInterfaceNames());
        self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => WebRuntimeAdapter::class === $property->getDeclaringClass()->getName()));
        usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
        self::assertSame(['application', 'bootstrapPermission', 'failureResponses', 'presenter', 'retired'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertFalse($property->isStatic());
        }
        self::assertFalse((new \ReflectionProperty(WebRuntimeAdapter::class, 'retired'))->isReadOnly());
        foreach (['application', 'bootstrapPermission', 'failureResponses', 'presenter'] as $property) {
            self::assertTrue((new \ReflectionProperty(WebRuntimeAdapter::class, $property))->isReadOnly());
        }
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPublic());
        self::assertSame([WebApplication::class, TrustedDeveloperBootstrapPermission::class], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $constructor->getParameters()));
        self::assertSame(['handle', 'isRetired'], $this->declaredPublicMethodNames($reflection));
        self::assertTrue($reflection->getMethod('__clone')->isPrivate());
        foreach (['__destruct', '__serialize', '__unserialize', 'reset', 'recover', 'retry', 'shutdown'] as $forbidden) {
            self::assertFalse($reflection->hasMethod($forbidden));
        }
    }

    public function testSuccessPreservesResponseAndExactEventOrderWithOneApplicationScope(): void
    {
        $events = [];
        $scope = new OperationScope();
        $request = new ServerRequest('GET', '/success');
        $response = self::createStub(ResponseInterface::class);
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturnCallback(static function () use (&$events, $scope): OperationScope {
            $events[] = 'begin';

            return $scope;
        });
        $kernel->expects(self::once())->method('endOperation')->with(self::identicalTo($scope))->willReturnCallback(static function () use (&$events): void {
            $events[] = 'end';
        });
        $application = $this->application($this->handler(static function (ServerRequestInterface $actual) use (&$events, $request, $response): ResponseInterface {
            self::assertSame($request, $actual);
            $events[] = 'application';

            return $response;
        }), $kernel);
        $delivery = $this->delivery(
            static function () use (&$events): AdapterControlledDeliveryPermission {
                $events[] = 'evidence';

                return AdapterControlledDeliveryPermission::Denied;
            },
            static function () use (&$events, $request): ServerRequestInterface {
                $events[] = 'request';

                return $request;
            },
            static function (ResponseInterface $actual) use (&$events, $response): void {
                self::assertSame($response, $actual);
                $events[] = 'send';
            },
            static function () use (&$events): void {
                $events[] = 'close';
            },
        );

        (new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Allowed))->handle($delivery);

        self::assertSame(['evidence', 'request', 'begin', 'application', 'end', 'send', 'close'], $events);
    }

    public function testBootstrapDenialSkipsEvidenceAndStillServesNormalWork(): void
    {
        $request = new ServerRequest('GET', '/bootstrap-denied');
        $response = self::createStub(ResponseInterface::class);
        $application = $this->application($this->handler(static fn(): ResponseInterface => $response));
        $evidenceCalls = 0;
        $sent = null;
        $delivery = $this->delivery(
            static function () use (&$evidenceCalls): AdapterControlledDeliveryPermission {
                ++$evidenceCalls;

                return AdapterControlledDeliveryPermission::Allowed;
            },
            static fn(): ServerRequestInterface => $request,
            static function (ResponseInterface $actual) use (&$sent): void {
                $sent = $actual;
            },
            static function (): void {},
        );

        (new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied))->handle($delivery);

        self::assertSame(0, $evidenceCalls);
        self::assertSame($response, $sent);
    }

    public function testEvidenceIsEvaluatedOnceBeforeRequestConversion(): void
    {
        $events = [];
        $request = new ServerRequest('GET', '/evidence-order');
        $application = $this->application($this->handler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class)));
        $delivery = $this->delivery(
            static function () use (&$events): AdapterControlledDeliveryPermission {
                $events[] = 'evidence';

                return AdapterControlledDeliveryPermission::Denied;
            },
            static function () use (&$events, $request): ServerRequestInterface {
                $events[] = 'request';

                return $request;
            },
            static function () use (&$events): void {
                $events[] = 'send';
            },
            static function () use (&$events): void {
                $events[] = 'close';
            },
        );

        (new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Allowed))->handle($delivery);

        self::assertSame(['evidence', 'request', 'send', 'close'], $events);
    }

    public function testEvidenceFailureUsesGenericWithoutRequestOrApplicationAccess(): void
    {
        $events = [];
        $application = $this->application($this->handler(static function (): ResponseInterface {
            self::fail('Application must not be called.');
        }));
        $delivery = $this->delivery(
            static function (): AdapterControlledDeliveryPermission {
                throw new RuntimeException('evidence-request-sentinel');
            },
            static function (): ServerRequestInterface {
                self::fail('Request conversion must not be called.');
            },
            static function (ResponseInterface $response) use (&$events): void {
                $events[] = 'send';
                self::assertGeneric($response);
            },
            static function () use (&$events): void {
                $events[] = 'close';
            },
        );

        (new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Allowed))->handle($delivery);

        self::assertSame(['send', 'close'], $events);
    }

    public function testConversionFailureUsesGenericWithoutApplicationAccess(): void
    {
        $application = $this->application($this->handler(static function (): ResponseInterface {
            self::fail('Application must not be called.');
        }));
        $closed = 0;
        $delivery = $this->delivery(
            static fn(): AdapterControlledDeliveryPermission => AdapterControlledDeliveryPermission::Denied,
            static function (): ServerRequestInterface {
                throw new RuntimeException('conversion-body-cookie-sentinel');
            },
            static function (ResponseInterface $response): void {
                self::assertGeneric($response);
            },
            static function () use (&$closed): void {
                ++$closed;
            },
        );

        (new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Allowed))->handle($delivery);

        self::assertSame(1, $closed);
    }

    public function testBootstrapAndDeliveryDenialBothPreventSupportedSourceAccess(): void
    {
        foreach ([
            [TrustedDeveloperBootstrapPermission::Denied, AdapterControlledDeliveryPermission::Allowed],
            [TrustedDeveloperBootstrapPermission::Allowed, AdapterControlledDeliveryPermission::Denied],
        ] as [$bootstrap, $permission]) {
            $source = new ExplosiveKernelDiagnosticException('request-source-sentinel');
            $application = $this->application($this->handler(static function () use ($source): ResponseInterface {
                throw $source;
            }));
            $delivery = $this->delivery(
                static fn(): AdapterControlledDeliveryPermission => $permission,
                static fn(): ServerRequestInterface => new ServerRequest('GET', '/denied'),
                static function (ResponseInterface $response): void {
                    self::assertGeneric($response);
                },
                static function (): void {},
            );

            (new WebRuntimeAdapter($application, $bootstrap))->handle($delivery);

            self::assertSame(0, $source->accessorCalls());
        }
    }

    public function testBothPermissionsAllowedSendExactTrustedHtmlAndHeaders(): void
    {
        $source = new LifecycleException(
            LifecycleDiagnostic::BeginOperationWhenNotBooted,
            'protected-native-message-sentinel',
            ['actual_state' => 'Unbooted', 'expected_state' => 'Booted'],
        );
        $expected = (new TrustedDeveloperDiagnosticHtmlPresenter())->renderIfEligible(
            new TrustedDeveloperPresentationEligibility(TrustedDeveloperBootstrapPermission::Allowed, AdapterControlledDeliveryPermission::Allowed),
            $source,
        );
        self::assertNotNull($expected);
        $application = $this->application($this->handler(static function () use ($source): ResponseInterface {
            throw $source;
        }));
        $delivery = $this->delivery(
            static fn(): AdapterControlledDeliveryPermission => AdapterControlledDeliveryPermission::Allowed,
            static fn(): ServerRequestInterface => new ServerRequest('GET', '/trusted'),
            static function (ResponseInterface $response) use ($expected): void {
                self::assertSame($expected, (string) $response->getBody());
                self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
                self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
                self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
                self::assertSame("default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
            },
            static function (): void {},
        );

        (new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Allowed))->handle($delivery);
    }

    public function testUnsupportedAndExplosiveAllowedFailuresFallBackToGenericWithoutRecursion(): void
    {
        $unsupported = new RuntimeException('unsupported-request-trace-sentinel');
        $explosive = new ExplosiveKernelDiagnosticException('explosive-request-sentinel');
        foreach ([$unsupported, $explosive] as $source) {
            $application = $this->application($this->handler(static function () use ($source): ResponseInterface {
                throw $source;
            }));
            $delivery = $this->delivery(
                static fn(): AdapterControlledDeliveryPermission => AdapterControlledDeliveryPermission::Allowed,
                static fn(): ServerRequestInterface => new ServerRequest('GET', '/fallback'),
                static function (ResponseInterface $response): void {
                    self::assertGeneric($response);
                },
                static function (): void {},
            );

            (new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Allowed))->handle($delivery);
        }

        self::assertSame(1, $explosive->accessorCalls());
    }

    public function testMappedApplicationFailureDoesNotRetireApplicationOrAdapter(): void
    {
        $application = $this->application($this->handler(static function (): ResponseInterface {
            throw new RuntimeException('ordinary-primary');
        }));
        $adapter = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);
        $delivery = $this->genericDelivery();

        $adapter->handle($delivery);

        self::assertFalse($application->isRetired());
        self::assertFalse($adapter->isRetired());
    }

    public function testApplicationCleanupFailureRetiresBothAfterServingCurrentGenericResponse(): void
    {
        $scope = new OperationScope();
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturn($scope);
        $kernel->expects(self::once())->method('endOperation')->with(self::identicalTo($scope))->willThrowException(new \LogicException('kernel-cleanup'));
        $application = $this->application($this->handler(static function (): ResponseInterface {
            throw new RuntimeException('application-primary');
        }), $kernel);
        $adapter = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);
        $closed = 0;
        $delivery = $this->delivery(
            static fn(): AdapterControlledDeliveryPermission => AdapterControlledDeliveryPermission::Allowed,
            static fn(): ServerRequestInterface => new ServerRequest('GET', '/cleanup'),
            static function (ResponseInterface $response): void {
                self::assertGeneric($response);
            },
            static function () use (&$closed): void {
                ++$closed;
            },
        );

        $adapter->handle($delivery);

        self::assertTrue($application->isRetired());
        self::assertTrue($adapter->isRetired());
        self::assertSame(1, $closed);
        $later = $this->createMock(WebDeliveryInterface::class);
        $later->expects(self::never())->method('request');
        $later->expects(self::never())->method('send');
        $later->expects(self::never())->method('close');
        $this->expectException(RetiredWebRuntimeException::class);
        $adapter->handle($later);
    }

    public function testSendOnlyFailurePropagatesExactlyClosesOnceAndRetires(): void
    {
        $send = new RuntimeException('send-failure');
        $closed = 0;
        $adapter = new WebRuntimeAdapter($this->successfulApplication(), TrustedDeveloperBootstrapPermission::Denied);
        $delivery = $this->delivery(
            static fn(): AdapterControlledDeliveryPermission => AdapterControlledDeliveryPermission::Allowed,
            static fn(): ServerRequestInterface => new ServerRequest('GET', '/send'),
            static function () use ($send): void {
                throw $send;
            },
            static function () use (&$closed): void {
                ++$closed;
            },
        );

        try {
            $adapter->handle($delivery);
            self::fail('Expected send failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($send, $actual);
        }
        self::assertSame(1, $closed);
        self::assertTrue($adapter->isRetired());
    }

    public function testCloseOnlyFailurePropagatesExactlyAndRetires(): void
    {
        $cleanup = new RuntimeException('close-failure');
        $adapter = new WebRuntimeAdapter($this->successfulApplication(), TrustedDeveloperBootstrapPermission::Denied);
        $delivery = $this->delivery(
            static fn(): AdapterControlledDeliveryPermission => AdapterControlledDeliveryPermission::Allowed,
            static fn(): ServerRequestInterface => new ServerRequest('GET', '/close'),
            static function (): void {},
            static function () use ($cleanup): void {
                throw $cleanup;
            },
        );

        try {
            $adapter->handle($delivery);
            self::fail('Expected close failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($cleanup, $actual);
        }
        self::assertTrue($adapter->isRetired());
    }

    public function testSendAndCloseFailurePreservesBothExactObjects(): void
    {
        $send = new RuntimeException('send-failure');
        $cleanup = new \LogicException('close-failure');
        $adapter = new WebRuntimeAdapter($this->successfulApplication(), TrustedDeveloperBootstrapPermission::Denied);
        $delivery = $this->delivery(
            static fn(): AdapterControlledDeliveryPermission => AdapterControlledDeliveryPermission::Allowed,
            static fn(): ServerRequestInterface => new ServerRequest('GET', '/combined'),
            static function () use ($send): void {
                throw $send;
            },
            static function () use ($cleanup): void {
                throw $cleanup;
            },
        );

        try {
            $adapter->handle($delivery);
            self::fail('Expected composite failure.');
        } catch (DeliveryCleanupException $actual) {
            self::assertSame($send, $actual->primaryFailure());
            self::assertSame($cleanup, $actual->cleanupFailure());
            self::assertSame($send, $actual->getPrevious());
        }
        self::assertTrue($adapter->isRetired());
    }

    public function testSequentialDeliveriesRetainNoCrossCallValues(): void
    {
        $firstRequest = new ServerRequest('GET', '/first');
        $secondRequest = new ServerRequest('GET', '/second');
        $firstResponse = self::createStub(ResponseInterface::class);
        $secondResponse = self::createStub(ResponseInterface::class);
        $application = $this->application($this->handler(static fn(ServerRequestInterface $request): ResponseInterface => $request === $firstRequest ? $firstResponse : $secondResponse));
        $adapter = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Allowed);
        $sent = [];
        foreach ([[$firstRequest, AdapterControlledDeliveryPermission::Denied], [$secondRequest, AdapterControlledDeliveryPermission::Allowed]] as [$request, $permission]) {
            $adapter->handle($this->delivery(
                static fn(): AdapterControlledDeliveryPermission => $permission,
                static fn(): ServerRequestInterface => $request,
                static function (ResponseInterface $response) use (&$sent): void {
                    $sent[] = $response;
                },
                static function (): void {},
            ));
        }

        self::assertSame([$firstResponse, $secondResponse], $sent);
        self::assertNotSame($firstRequest, $secondRequest);
        self::assertNotSame($firstResponse, $secondResponse);
        self::assertFalse($adapter->isRetired());
    }

    public function testReentrantDeliveriesKeepLocalIdentitiesAndCloseInnerBeforeOuter(): void
    {
        $events = [];
        $outerRequest = new ServerRequest('GET', '/outer');
        $innerRequest = new ServerRequest('GET', '/inner');
        $outerResponse = self::createStub(ResponseInterface::class);
        $innerResponse = self::createStub(ResponseInterface::class);
        $adapter = null;
        $innerDelivery = null;
        $application = $this->application($this->handler(static function (ServerRequestInterface $request) use (&$adapter, &$innerDelivery, &$events, $outerRequest, $outerResponse, $innerResponse): ResponseInterface {
            if ($request === $outerRequest) {
                $events[] = 'outer application';
                self::assertInstanceOf(WebRuntimeAdapter::class, $adapter);
                self::assertInstanceOf(WebDeliveryInterface::class, $innerDelivery);
                $adapter->handle($innerDelivery);

                return $outerResponse;
            }
            $events[] = 'inner application';

            return $innerResponse;
        }));
        $adapter = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Allowed);
        $innerDelivery = $this->delivery(
            static function () use (&$events): AdapterControlledDeliveryPermission {
                $events[] = 'inner evidence';

                return AdapterControlledDeliveryPermission::Denied;
            },
            static function () use (&$events, $innerRequest): ServerRequestInterface {
                $events[] = 'inner request';

                return $innerRequest;
            },
            static function (ResponseInterface $response) use (&$events, $innerResponse): void {
                self::assertSame($innerResponse, $response);
                $events[] = 'inner send';
            },
            static function () use (&$events): void {
                $events[] = 'inner close';
            },
        );
        $outerDelivery = $this->delivery(
            static function () use (&$events): AdapterControlledDeliveryPermission {
                $events[] = 'outer evidence';

                return AdapterControlledDeliveryPermission::Denied;
            },
            static function () use (&$events, $outerRequest): ServerRequestInterface {
                $events[] = 'outer request';

                return $outerRequest;
            },
            static function (ResponseInterface $response) use (&$events, $outerResponse): void {
                self::assertSame($outerResponse, $response);
                $events[] = 'outer send';
            },
            static function () use (&$events): void {
                $events[] = 'outer close';
            },
        );

        $adapter->handle($outerDelivery);

        self::assertSame(['outer evidence', 'outer request', 'outer application', 'inner evidence', 'inner request', 'inner application', 'inner send', 'inner close', 'outer send', 'outer close'], $events);
    }

    public function testPreRetiredApplicationAndRetiredAdapterRefuseBeforeDeliveryAccess(): void
    {
        $scope = new OperationScope();
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('beginOperation')->willReturn($scope);
        $kernel->method('endOperation')->willThrowException(new RuntimeException('cleanup'));
        $application = $this->application($this->handler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class)), $kernel);
        try {
            $application->handle(new ServerRequest('GET', '/retire'));
        } catch (RuntimeException) {
        }
        self::assertTrue($application->isRetired());
        $adapter = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);
        $delivery = $this->createMock(WebDeliveryInterface::class);
        $delivery->expects(self::never())->method('evaluateTrustedPresentationPermission');
        $delivery->expects(self::never())->method('request');
        $delivery->expects(self::never())->method('send');
        $delivery->expects(self::never())->method('close');
        try {
            $adapter->handle($delivery);
            self::fail('Expected pre-retired refusal.');
        } catch (RetiredWebRuntimeException) {
        }
        self::assertTrue($adapter->isRetired());

        $later = $this->createMock(WebDeliveryInterface::class);
        $later->expects(self::never())->method('request');
        $this->expectException(RetiredWebRuntimeException::class);
        $adapter->handle($later);
    }

    public function testDestructionPerformsNoSendCloseShutdownOrIo(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::never())->method('shutdown');
        $application = $this->application($this->handler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class)), $kernel);
        $delivery = $this->createMock(WebDeliveryInterface::class);
        $delivery->expects(self::never())->method('send');
        $delivery->expects(self::never())->method('close');
        $adapter = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);

        unset($adapter, $delivery);
        gc_collect_cycles();

        self::assertFalse($application->isRetired());
    }

    private function application(RequestHandlerInterface $handler, ?KernelInterface $kernel = null): WebApplication
    {
        if (null === $kernel) {
            $kernel = self::createStub(KernelInterface::class);
            $kernel->method('beginOperation')->willReturnCallback(static fn(): OperationScope => new OperationScope());
        }

        return WebApplication::boot($kernel, new RouteTable([]), [], $handler);
    }

    private function successfulApplication(): WebApplication
    {
        return $this->application($this->handler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class)));
    }

    private function handler(Closure $callback): RequestHandlerInterface
    {
        return new readonly class ($callback) implements RequestHandlerInterface {
            /** @param Closure(ServerRequestInterface): ResponseInterface $callback */
            public function __construct(private Closure $callback) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->callback)($request);
            }
        };
    }

    private function delivery(Closure $evaluate, Closure $request, Closure $send, Closure $close): WebDeliveryInterface
    {
        return new readonly class ($evaluate, $request, $send, $close) implements WebDeliveryInterface {
            /**
             * @param Closure(): AdapterControlledDeliveryPermission $evaluate
             * @param Closure(): ServerRequestInterface $requestFactory
             * @param Closure(ResponseInterface): void $sender
             * @param Closure(): void $closer
             */
            public function __construct(
                private Closure $evaluate,
                private Closure $requestFactory,
                private Closure $sender,
                private Closure $closer,
            ) {}

            public function evaluateTrustedPresentationPermission(): AdapterControlledDeliveryPermission
            {
                return ($this->evaluate)();
            }

            public function request(): ServerRequestInterface
            {
                return ($this->requestFactory)();
            }

            public function send(ResponseInterface $response): void
            {
                ($this->sender)($response);
            }

            public function close(): void
            {
                ($this->closer)();
            }
        };
    }

    private function genericDelivery(): WebDeliveryInterface
    {
        return $this->delivery(
            static fn(): AdapterControlledDeliveryPermission => AdapterControlledDeliveryPermission::Denied,
            static fn(): ServerRequestInterface => new ServerRequest('GET', '/generic'),
            static function (ResponseInterface $response): void {
                self::assertGeneric($response);
            },
            static function (): void {},
        );
    }

    private static function assertGeneric(ResponseInterface $response): void
    {
        self::assertSame(500, $response->getStatusCode());
        self::assertSame("Internal Server Error\n", (string) $response->getBody());
        self::assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertFalse($response->hasHeader('Content-Security-Policy'));
        foreach (['request', 'cookie', 'body', 'trace', 'Exception', 'KGN-'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, (string) $response->getBody());
        }
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
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName()
                    && !$method->isConstructor(),
            ),
        );
        sort($names);

        return $names;
    }
}
