<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Http\Server\Tests\Fixture\CallbackRequestHandler;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Exception\InvalidRouteRequestException;
use Kongono\Routing\Match\RouteMatch;
use Kongono\Routing\Matcher\RouteMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class RoutingRequestHandlerTest extends TestCase
{
    public function testEmptyRoutesDelegateExactOriginalRequestToUnmatchedHandler(): void
    {
        $request = new ServerRequest('GET', '/');
        $response = self::createStub(ResponseInterface::class);
        $unmatched = new CallbackRequestHandler(static function (ServerRequestInterface $actual) use ($request, $response): ResponseInterface {
            self::assertSame($request, $actual);

            return $response;
        });
        $handler = new RoutingRequestHandler(new RouteTable([]), [], $unmatched);

        self::assertSame($response, $handler->handle($request));
        self::assertSame([], $handler->handlers());
        self::assertSame($unmatched, $handler->unmatchedHandler());
    }

    public function testMappingsStoreInCanonicalOrderDetachReferencesAndReturnFreshArrays(): void
    {
        $routes = new RouteTable([
            new RouteDefinition('zeta', ['GET'], '/zeta'),
            new RouteDefinition('alpha', ['GET'], '/alpha'),
        ]);
        $alpha = $this->responseHandler(new Response(201));
        $zeta = $this->responseHandler(new Response(202));
        $replacement = $this->responseHandler(new Response(203));
        $slot = $zeta;
        $handlers = ['zeta' => &$slot, 'alpha' => $alpha];
        $unmatched = $this->responseHandler(new Response(404));
        $routing = new RoutingRequestHandler($routes, $handlers, $unmatched);
        $slot = $replacement;

        self::assertSame(['alpha', 'zeta'], array_keys($routing->handlers()));
        self::assertSame($alpha, $routing->handlers()['alpha']);
        self::assertSame($zeta, $routing->handlers()['zeta']);
        $returned = $routing->handlers();
        $returned['alpha'] = $replacement;
        self::assertSame($alpha, $routing->handlers()['alpha']);
        self::assertSame($routes, $routing->routes());
        self::assertSame($unmatched, $routing->unmatchedHandler());
    }

    public function testKeyFailuresPrecedeValuesAndUseOnlyCounts(): void
    {
        $routes = new RouteTable([
            new RouteDefinition('alpha', ['GET'], '/alpha'),
            new RouteDefinition('beta', ['GET'], '/beta'),
        ]);
        $invalidMaps = [
            ['alpha' => 'invalid'],
            ['alpha' => 'invalid', 'beta' => 'invalid', 'extra' => 'invalid'],
            ['alpha' => 'invalid', 'extra' => 'invalid'],
            [0 => 'invalid', 'alpha' => 'invalid'],
        ];

        foreach ($invalidMaps as $handlers) {
            try {
                new RoutingRequestHandler($routes, $handlers, $this->responseHandler(new Response(404)));
                self::fail('Expected route-handler key failure.');
            } catch (InvalidHttpServerDefinitionException $exception) {
                self::assertSame(HttpServerDiagnostic::InvalidRouteHandlerKeys->value, $exception->diagnosticId());
                self::assertSame(['route_count', 'handler_count', 'missing_count', 'unexpected_count'], array_map(static fn($field): string => $field->key(), $exception->diagnosticContext()->fields()));
            }
        }
    }

    public function testKeyFailureDiagnosticIsIndependentOfCallerMapInsertionOrder(): void
    {
        $routes = new RouteTable([
            new RouteDefinition('alpha', ['GET'], '/alpha'),
            new RouteDefinition('beta', ['GET'], '/beta'),
        ]);
        $validHandler = $this->responseHandler(new Response(200));
        $exceptions = [];

        foreach ([
            ['alpha' => $validHandler, 'extra' => $validHandler],
            ['extra' => $validHandler, 'alpha' => $validHandler],
        ] as $handlers) {
            try {
                new RoutingRequestHandler($routes, $handlers, $validHandler);
                self::fail('Expected route-handler key failure.');
            } catch (InvalidHttpServerDefinitionException $exception) {
                $exceptions[] = $exception;
            }
        }

        self::assertCount(2, $exceptions);
        [$forward, $reversed] = $exceptions;
        foreach ($exceptions as $exception) {
            self::assertSame(HttpServerDiagnostic::InvalidRouteHandlerKeys->value, $exception->diagnosticId());
            self::assertSame(
                ['route_count', 'handler_count', 'missing_count', 'unexpected_count'],
                array_map(static fn($field): string => $field->key(), $exception->diagnosticContext()->fields()),
            );
            self::assertSame(
                [2, 2, 1, 1],
                array_map(static fn($field): string|int|bool => $field->value(), $exception->diagnosticContext()->fields()),
            );
        }

        self::assertSame($forward->diagnosticId(), $reversed->diagnosticId());
        self::assertSame($forward->getMessage(), $reversed->getMessage());
        self::assertSame($forward->diagnosticTitle(), $reversed->diagnosticTitle());
        self::assertSame($forward->diagnosticRemediation(), $reversed->diagnosticRemediation());
        self::assertSame($forward->diagnosticContext()->operation(), $reversed->diagnosticContext()->operation());
        self::assertSame($forward->diagnosticContext()->violatedInvariant(), $reversed->diagnosticContext()->violatedInvariant());

        $fieldSurface = static fn(InvalidHttpServerDefinitionException $exception): array => array_map(
            static fn($field): array => [
                $field->key(),
                $field->label(),
                $field->kind(),
                $field->disclosure(),
                $field->textWasTruncated(),
                $field->value(),
            ],
            $exception->diagnosticContext()->fields(),
        );
        self::assertSame($fieldSurface($forward), $fieldSurface($reversed));

        $schemaFields = HttpServerDiagnostic::InvalidRouteHandlerKeys->contextFields();
        self::assertSame(
            ['route_count', 'handler_count', 'missing_count', 'unexpected_count'],
            array_map(static fn($field): string => $field->key(), $schemaFields),
        );
        foreach ($schemaFields as $field) {
            self::assertTrue($field->isRequired());
        }

        foreach ($exceptions as $exception) {
            $surface = json_encode([
                $exception->getMessage(),
                $exception->diagnosticId(),
                $exception->diagnosticTitle(),
                $exception->diagnosticRemediation(),
                $exception->diagnosticContext()->operation(),
                $exception->diagnosticContext()->violatedInvariant(),
                $fieldSurface($exception),
            ], JSON_THROW_ON_ERROR);
            self::assertStringNotContainsString('beta', $surface);
            self::assertStringNotContainsString('extra', $surface);
        }
    }

    public function testValueFailureUsesFirstCanonicalPositionIndependentOfInputOrder(): void
    {
        $routes = new RouteTable([
            new RouteDefinition('beta', ['GET'], '/beta'),
            new RouteDefinition('alpha', ['GET'], '/alpha'),
        ]);
        foreach ([['beta' => 'bad beta', 'alpha' => 'bad alpha'], ['alpha' => 'bad alpha', 'beta' => 'bad beta']] as $handlers) {
            try {
                new RoutingRequestHandler($routes, $handlers, $this->responseHandler(new Response(404)));
                self::fail('Expected route-handler value failure.');
            } catch (InvalidHttpServerDefinitionException $exception) {
                self::assertSame(HttpServerDiagnostic::InvalidRouteHandlerValue->value, $exception->diagnosticId());
                self::assertSame([0, 'not_request_handler'], array_map(static fn($field): string|int|bool => $field->value(), $exception->diagnosticContext()->fields()));
            }
        }
    }

    public function testStaticAndParameterizedMatchesSelectExactHandlerAndFreshMatchAttribute(): void
    {
        $routes = new RouteTable([
            new RouteDefinition('static', ['GET'], '/users/new'),
            new RouteDefinition('parameter', ['GET'], '/users/{id}'),
        ]);
        $staticResponse = new Response(201);
        $parameterResponse = new Response(202);
        $seenMatches = [];
        $static = new CallbackRequestHandler(static function (ServerRequestInterface $request) use ($staticResponse, &$seenMatches): ResponseInterface {
            $match = $request->getAttribute(RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE);
            self::assertInstanceOf(RouteMatch::class, $match);
            $seenMatches[] = $match;
            self::assertSame('static', $match->route()->name());

            return $staticResponse;
        });
        $parameter = new CallbackRequestHandler(static function (ServerRequestInterface $request) use ($parameterResponse, &$seenMatches): ResponseInterface {
            $match = $request->getAttribute(RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE);
            self::assertInstanceOf(RouteMatch::class, $match);
            $seenMatches[] = $match;
            self::assertSame('parameter', $match->route()->name());
            self::assertSame(['id' => 'a%2Fb'], $match->parameters());

            return $parameterResponse;
        });
        $routing = new RoutingRequestHandler($routes, ['parameter' => $parameter, 'static' => $static], $this->responseHandler(new Response(404)));

        self::assertSame($staticResponse, $routing->handle(new ServerRequest('GET', '/users/new')));
        self::assertSame($parameterResponse, $routing->handle(new ServerRequest('GET', '/users/a%2Fb')));
        self::assertNotSame($seenMatches[0], $seenMatches[1]);
    }

    public function testReservedAttributeOverwritesWithoutReadingAndOriginalRemainsUnchanged(): void
    {
        $routes = new RouteTable([new RouteDefinition('root', ['GET'], '/')]);
        $original = (new ServerRequest('GET', '/'))->withAttribute(RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE, 'forged');
        $selected = new CallbackRequestHandler(static function (ServerRequestInterface $request): ResponseInterface {
            self::assertInstanceOf(RouteMatch::class, $request->getAttribute(RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE));

            return new Response(204);
        });
        $routing = new RoutingRequestHandler($routes, ['root' => $selected], $this->responseHandler(new Response(404)));

        self::assertSame(204, $routing->handle($original)->getStatusCode());
        self::assertSame('forged', $original->getAttribute(RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE));

        $uri = self::createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/');
        $derived = self::createStub(ServerRequestInterface::class);
        $request = self::createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getUri')->willReturn($uri);
        $request->expects(self::never())->method('getAttribute');
        $request->expects(self::once())->method('withAttribute')
            ->with(RouteMatch::class, self::isInstanceOf(RouteMatch::class))
            ->willReturn($derived);
        $selectedDerived = new CallbackRequestHandler(static function (ServerRequestInterface $actual) use ($derived): ResponseInterface {
            self::assertSame($derived, $actual);

            return new Response(200);
        });
        self::assertSame(200, (new RoutingRequestHandler($routes, ['root' => $selectedDerived], $this->responseHandler(new Response(404))))->handle($request)->getStatusCode());
    }

    public function testOrdinaryUnmatchedUsesOriginalWithoutAttributeMutation(): void
    {
        $routes = new RouteTable([new RouteDefinition('known', ['GET'], '/known')]);
        $original = new ServerRequest('GET', '/missing');
        $unmatchedResponse = new Response(404);
        $unmatched = new CallbackRequestHandler(static function (ServerRequestInterface $request) use ($original, $unmatchedResponse): ResponseInterface {
            self::assertSame($original, $request);
            self::assertFalse($request->hasHeader('x-unused'));
            self::assertNull($request->getAttribute(RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE));

            return $unmatchedResponse;
        });
        $routing = new RoutingRequestHandler($routes, ['known' => $this->responseHandler(new Response(200))], $unmatched);

        self::assertSame($unmatchedResponse, $routing->handle($original));
    }

    public function testInvalidRoutingInputFailsBeforeAnyHandlerCall(): void
    {
        $uri = self::createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/');
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('invalid method');
        $request->method('getUri')->willReturn($uri);
        $never = new CallbackRequestHandler(static function (): ResponseInterface {
            self::fail('No handler may be called for invalid routing input.');
        });
        $routing = new RoutingRequestHandler(new RouteTable([new RouteDefinition('root', ['GET'], '/')]), ['root' => $never], $never);

        $this->expectException(InvalidRouteRequestException::class);
        $routing->handle($request);
    }

    public function testWithAttributeSelectedAndUnmatchedThrowablesPropagateByIdentity(): void
    {
        $routes = new RouteTable([new RouteDefinition('root', ['GET'], '/')]);
        $uri = self::createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/');
        $withAttributeFailure = new RuntimeException('attribute');
        $request = self::createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getUri')->willReturn($uri);
        $request->expects(self::once())->method('withAttribute')->willThrowException($withAttributeFailure);
        $unused = $this->responseHandler(new Response(200));
        try {
            (new RoutingRequestHandler($routes, ['root' => $unused], $unused))->handle($request);
            self::fail('Expected withAttribute failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($withAttributeFailure, $actual);
        }

        $selectedFailure = new RuntimeException('selected');
        $selected = new CallbackRequestHandler(static function () use ($selectedFailure): ResponseInterface {
            throw $selectedFailure;
        });
        try {
            (new RoutingRequestHandler($routes, ['root' => $selected], $unused))->handle(new ServerRequest('GET', '/'));
            self::fail('Expected selected failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($selectedFailure, $actual);
        }

        $unmatchedFailure = new RuntimeException('unmatched');
        $unmatched = new CallbackRequestHandler(static function () use ($unmatchedFailure): ResponseInterface {
            throw $unmatchedFailure;
        });
        try {
            (new RoutingRequestHandler($routes, ['root' => $unused], $unmatched))->handle(new ServerRequest('GET', '/missing'));
            self::fail('Expected unmatched failure.');
        } catch (RuntimeException $actual) {
            self::assertSame($unmatchedFailure, $actual);
        }
    }

    public function testAlternatingMatchesAndUnmatchedCallsLeakNoState(): void
    {
        $seen = [];
        $selected = new CallbackRequestHandler(static function (ServerRequestInterface $request) use (&$seen): ResponseInterface {
            $match = $request->getAttribute(RouteMatch::class);
            self::assertInstanceOf(RouteMatch::class, $match);
            $seen[] = $match->parameters()['id'];

            return new Response(200);
        });
        $unmatched = new CallbackRequestHandler(static function (ServerRequestInterface $request) use (&$seen): ResponseInterface {
            $seen[] = 'unmatched:' . $request->getUri()->getPath();

            return new Response(404);
        });
        $routing = new RoutingRequestHandler(
            new RouteTable([new RouteDefinition('item', ['GET'], '/items/{id}')]),
            ['item' => $selected],
            $unmatched,
        );
        $routing->handle(new ServerRequest('GET', '/items/one'));
        $routing->handle(new ServerRequest('GET', '/missing'));
        $routing->handle(new ServerRequest('GET', '/items/two'));

        self::assertSame(['one', 'unmatched:/missing', 'two'], $seen);
    }

    public function testShapeDependenciesAndSourceAccessAreExact(): void
    {
        self::assertSame(RouteMatch::class, RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE);
        $reflection = new \ReflectionClass(RoutingRequestHandler::class);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertTrue($reflection->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => RoutingRequestHandler::class === $property->getDeclaringClass()->getName()));
        $names = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties);
        sort($names);
        self::assertSame(['handlers', 'matcher', 'routes', 'unmatchedHandler'], $names);
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
        }
        $methods = array_values(array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => RoutingRequestHandler::class === $method->getDeclaringClass()->getName()));
        $methodNames = array_map(static fn(\ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);
        self::assertSame(['__construct', 'handle', 'handlers', 'routes', 'unmatchedHandler'], $methodNames);
        self::assertSame([RouteTable::class, 'array', RequestHandlerInterface::class], array_map(static fn($parameter): string => (string) $parameter->getType(), $reflection->getConstructor()?->getParameters() ?? []));

        $source = file_get_contents(dirname(__DIR__, 2) . '/src/RoutingRequestHandler.php');
        self::assertIsString($source);
        self::assertSame(1, substr_count($source, '->withAttribute('));
        self::assertStringNotContainsString('->getAttribute(', $source);
        foreach (['->getHeaders(', '->getBody(', '->getCookieParams(', '->getQueryParams(', '->getServerParams(', '->getParsedBody(', '->getUploadedFiles(', '->getAttributes(', '->getRequestTarget('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        $matcherType = (new \ReflectionProperty(RoutingRequestHandler::class, 'matcher'))->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $matcherType);
        self::assertSame(RouteMatcher::class, $matcherType->getName());
    }

    private function responseHandler(ResponseInterface $response): CallbackRequestHandler
    {
        return new CallbackRequestHandler(static fn(ServerRequestInterface $request): ResponseInterface => $response);
    }
}
