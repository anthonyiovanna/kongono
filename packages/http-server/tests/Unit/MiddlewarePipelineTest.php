<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException;
use Kongono\Http\Server\Internal\MiddlewareLayer;
use Kongono\Http\Server\MiddlewarePipeline;
use Kongono\Http\Server\Tests\Fixture\CallbackMiddleware;
use Kongono\Http\Server\Tests\Fixture\CallbackRequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class MiddlewarePipelineTest extends TestCase
{
    public function testZeroMiddlewareDelegatesExactPsrRequestAndResponse(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $response = self::createStub(ResponseInterface::class);
        $terminal = new CallbackRequestHandler(static function (ServerRequestInterface $actual) use ($request, $response): ResponseInterface {
            self::assertSame($request, $actual);

            return $response;
        });
        $pipeline = new MiddlewarePipeline($terminal);

        self::assertSame($response, $pipeline->handle($request));
        self::assertSame([], $pipeline->middleware());
        self::assertSame($terminal, $pipeline->terminalHandler());
    }

    public function testOrderDerivedRequestAndReverseResponseReplacementAreExact(): void
    {
        $events = [];
        $original = new ServerRequest('GET', '/');
        $derived = $original->withAttribute('derived', true);
        $terminalResponse = self::createStub(ResponseInterface::class);
        $replacement = self::createStub(ResponseInterface::class);
        $terminal = new CallbackRequestHandler(static function (ServerRequestInterface $request) use (&$events, $derived, $terminalResponse): ResponseInterface {
            $events[] = 'terminal';
            self::assertSame($derived, $request);

            return $terminalResponse;
        });
        $a = new CallbackMiddleware(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use (&$events, $replacement): ResponseInterface {
            $events[] = 'a enter';
            $next->handle($request);
            $events[] = 'a leave';

            return $replacement;
        });
        $b = new CallbackMiddleware(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use (&$events, $derived): ResponseInterface {
            $events[] = 'b enter';
            $response = $next->handle($derived);
            $events[] = 'b leave';

            return $response;
        });
        $pipeline = new MiddlewarePipeline($terminal, $a, $b);

        self::assertSame($replacement, $pipeline->handle($original));
        self::assertSame(['a enter', 'b enter', 'terminal', 'b leave', 'a leave'], $events);
    }

    public function testShortCircuitPreventsDownstreamAccess(): void
    {
        $shortResponse = self::createStub(ResponseInterface::class);
        $terminal = new CallbackRequestHandler(static function (): ResponseInterface {
            self::fail('Terminal must not be called.');
        });
        $later = new CallbackMiddleware(static function (): ResponseInterface {
            self::fail('Later middleware must not be called.');
        });
        $short = new CallbackMiddleware(static fn(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface => $shortResponse);

        self::assertSame($shortResponse, (new MiddlewarePipeline($terminal, $short, $later))->handle(self::createStub(ServerRequestInterface::class)));
    }

    public function testMiddlewareMayInvokeNextTwiceWithoutSharedCursor(): void
    {
        $calls = 0;
        $first = self::createStub(ResponseInterface::class);
        $second = self::createStub(ResponseInterface::class);
        $terminal = new CallbackRequestHandler(static function () use (&$calls, $first, $second): ResponseInterface {
            ++$calls;

            return 1 === $calls ? $first : $second;
        });
        $twice = new CallbackMiddleware(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($first): ResponseInterface {
            self::assertSame($first, $next->handle($request));

            return $next->handle($request);
        });

        self::assertSame($second, (new MiddlewarePipeline($terminal, $twice))->handle(self::createStub(ServerRequestInterface::class)));
        self::assertSame(2, $calls);
    }

    public function testMiddlewareAndTerminalThrowablesPropagateByIdentity(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $middlewareFailure = new RuntimeException('middleware');
        $middleware = new CallbackMiddleware(static function () use ($middlewareFailure): ResponseInterface {
            throw $middlewareFailure;
        });
        $unusedTerminal = new CallbackRequestHandler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class));
        try {
            (new MiddlewarePipeline($unusedTerminal, $middleware))->handle($request);
            self::fail('Expected middleware failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($middlewareFailure, $actual);
        }

        $terminalFailure = new RuntimeException('terminal');
        $terminal = new CallbackRequestHandler(static function () use ($terminalFailure): ResponseInterface {
            throw $terminalFailure;
        });
        $delegating = new CallbackMiddleware(static fn(ServerRequestInterface $actual, RequestHandlerInterface $next): ResponseInterface => $next->handle($actual));
        try {
            (new MiddlewarePipeline($terminal, $delegating))->handle($request);
            self::fail('Expected terminal failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($terminalFailure, $actual);
        }
    }

    public function testBoundsDetachmentAndReturnedArrayIsolationAreExact(): void
    {
        $terminal = new CallbackRequestHandler(static fn(): ResponseInterface => self::createStub(ResponseInterface::class));
        $first = new CallbackMiddleware(static fn(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface => $next->handle($request));
        $replacement = new CallbackMiddleware(static fn(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface => $next->handle($request));
        $slot = $first;
        $source = [&$slot];
        $pipeline = new MiddlewarePipeline($terminal, ...$source);
        $slot = $replacement;
        self::assertSame([$first], $pipeline->middleware());
        $returned = $pipeline->middleware();
        $returned[0] = $replacement;
        self::assertSame([$first], $pipeline->middleware());

        $valid = array_fill(0, MiddlewarePipeline::MAX_MIDDLEWARE, $first);
        self::assertCount(128, (new MiddlewarePipeline($terminal, ...$valid))->middleware());
        try {
            new MiddlewarePipeline($terminal, ...array_fill(0, 129, $first));
            self::fail('Expected middleware limit failure.');
        } catch (InvalidHttpServerDefinitionException $exception) {
            self::assertSame(HttpServerDiagnostic::TooManyMiddleware->value, $exception->diagnosticId());
            self::assertSame([129, 128], array_map(static fn($field): string|int|bool => $field->value(), $exception->diagnosticContext()->fields()));
        }
    }

    public function testAlternatingAndReentrantCallsLeakNoState(): void
    {
        $events = [];
        $pipeline = null;
        $nested = new ServerRequest('GET', '/nested');
        $terminal = new CallbackRequestHandler(static function (ServerRequestInterface $request) use (&$events): ResponseInterface {
            $events[] = $request->getUri()->getPath();

            return self::createStub(ResponseInterface::class);
        });
        $middleware = new CallbackMiddleware(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use (&$pipeline, $nested): ResponseInterface {
            if ('/outer' === $request->getUri()->getPath()) {
                self::assertInstanceOf(MiddlewarePipeline::class, $pipeline);
                $pipeline->handle($nested);
            }

            return $next->handle($request);
        });
        $pipeline = new MiddlewarePipeline($terminal, $middleware);
        $pipeline->handle(new ServerRequest('GET', '/outer'));
        $pipeline->handle(new ServerRequest('GET', '/after'));

        self::assertSame(['/nested', '/outer', '/after'], $events);
    }

    public function testPublicAndInternalShapesAreExact(): void
    {
        $pipeline = new \ReflectionClass(MiddlewarePipeline::class);
        self::assertTrue($pipeline->isFinal());
        self::assertTrue($pipeline->isReadOnly());
        self::assertTrue($pipeline->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(ApiStatus::Experimental, $pipeline->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame(128, $pipeline->getConstant('MAX_MIDDLEWARE'));
        self::assertSame(['entryHandler', 'middleware', 'terminalHandler'], $this->propertyNames($pipeline));
        self::assertSame(['__construct', 'handle', 'middleware', 'terminalHandler'], $this->publicMethodNames($pipeline));

        $layer = new \ReflectionClass(MiddlewareLayer::class);
        self::assertTrue($layer->isFinal());
        self::assertTrue($layer->isReadOnly());
        self::assertTrue($layer->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(ApiStatus::Internal, $layer->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame(['middleware', 'nextHandler'], $this->propertyNames($layer));
        self::assertSame(['__construct', 'handle'], $this->publicMethodNames($layer));
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function propertyNames(\ReflectionClass $reflection): array
    {
        $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $reflection->getName() === $property->getDeclaringClass()->getName()));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
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
    private function publicMethodNames(\ReflectionClass $reflection): array
    {
        $methods = array_values(array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName()));
        $names = array_map(static fn(\ReflectionMethod $method): string => $method->getName(), $methods);
        sort($names);

        return $names;
    }
}
