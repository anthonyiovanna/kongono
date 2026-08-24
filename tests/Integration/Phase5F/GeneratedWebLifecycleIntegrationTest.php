<?php

declare(strict_types=1);

namespace Kongono\Tests\Integration\Phase5F;

use Closure;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Server\MiddlewarePipeline;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Kernel\Lifecycle\OperationScope;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use Kongono\Web\Runtime\GeneratedWebRuntimeApplication;
use Kongono\Web\Runtime\GeneratedWebRuntimeBootstrap;
use Kongono\Web\Runtime\Tests\Fixture\Phase5FApplicationAssembly;
use Kongono\Web\Runtime\Tests\Fixture\Phase5FOperationAssembly;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionMethod;

final class EventLog
{
    /** @var list<string> */
    private array $events = [];

    public function record(string $event): void
    {
        $this->events[] = $event;
    }

    /** @return list<string> */
    public function events(): array
    {
        return $this->events;
    }
}

final class GeneratedWebLifecycleIntegrationTest extends TestCase
{
    public function testRealRoutingMiddlewareAndRuntimeRetainExistingLifecycleOwnership(): void
    {
        Phase5FApplicationAssembly::$beginOperationCalls = 0;
        Phase5FApplicationAssembly::$shutdownCalls = 0;
        $events = new EventLog();
        $matchedResponse = new Response(201);
        $unmatchedResponse = new Response(404);
        $matchedHandler = $this->handler(static function (ServerRequestInterface $request) use ($events, $matchedResponse): ResponseInterface {
            $events->record('matched');

            return $matchedResponse;
        });
        $unmatchedHandler = $this->handler(static function (ServerRequestInterface $request) use ($events, $unmatchedResponse): ResponseInterface {
            $events->record('unmatched');

            return $unmatchedResponse;
        });
        $middleware = new readonly class ($events) implements MiddlewareInterface {
            public function __construct(private EventLog $events) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->events->record('middleware-in');
                $response = $handler->handle($request);
                $this->events->record('middleware-out');

                return $response;
            }
        };
        $routing = new RoutingRequestHandler(
            new RouteTable([new RouteDefinition('matched', ['GET'], '/matched')]),
            ['matched' => $matchedHandler],
            $unmatchedHandler,
        );
        $pipeline = new MiddlewarePipeline($routing, $middleware);
        $firstScope = new OperationScope();
        $secondScope = new OperationScope();
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::exactly(2))->method('beginOperation')->willReturnOnConsecutiveCalls($firstScope, $secondScope);
        $kernel->expects(self::exactly(2))->method('endOperation')->willReturnCallback(static function (OperationScope $scope) use ($events): void {
            $events->record('kernel-end');
        });
        $kernel->expects(self::once())->method('shutdown');
        $web = new WebApplication($kernel, $pipeline);
        $loaded = new LoadedGeneratedWiring(
            str_repeat('f', 64),
            Phase5FApplicationAssembly::class,
            Phase5FOperationAssembly::class,
            new ReflectionMethod(Phase5FApplicationAssembly::class, 'start'),
        );
        $application = GeneratedWebRuntimeApplication::start(
            $loaded,
            $web,
            new GeneratedWebRuntimeBootstrap(TrustedDeveloperBootstrapPermission::Denied),
        );
        $runtime = $application->runtime();

        $runtime->handle($this->delivery(new ServerRequest('GET', '/matched'), $matchedResponse, $events));
        $runtime->handle($this->delivery(new ServerRequest('GET', '/missing'), $unmatchedResponse, $events));
        $application->shutdown();
        $application->shutdown();

        self::assertSame(0, Phase5FApplicationAssembly::$beginOperationCalls);
        self::assertSame(1, Phase5FApplicationAssembly::$shutdownCalls);
        self::assertSame([
            'middleware-in', 'matched', 'middleware-out', 'kernel-end', 'send-201', 'close',
            'middleware-in', 'unmatched', 'middleware-out', 'kernel-end', 'send-404', 'close',
        ], $events->events());
    }

    /** @param Closure(ServerRequestInterface): ResponseInterface $callback */
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

    private function delivery(ServerRequestInterface $request, ResponseInterface $expected, EventLog $events): WebDeliveryInterface
    {
        $delivery = $this->createMock(WebDeliveryInterface::class);
        $delivery->expects(self::never())->method('evaluateTrustedPresentationPermission');
        $delivery->expects(self::once())->method('request')->willReturn($request);
        $delivery->expects(self::once())->method('send')->willReturnCallback(static function (ResponseInterface $response) use ($expected, $events): void {
            self::assertSame($expected, $response);
            $events->record('send-' . $response->getStatusCode());
        });
        $delivery->expects(self::once())->method('close')->willReturnCallback(static function () use ($events): void {
            $events->record('close');
        });

        return $delivery;
    }
}
