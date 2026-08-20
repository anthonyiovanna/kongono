<?php

declare(strict_types=1);

namespace Kongono\Routing\Tests\Unit\Matcher;

use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\InvalidRouteRequestException;
use Kongono\Routing\Matcher\RouteMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

final class RouteMatcherTest extends TestCase
{
    public function testLiteralSpecificityWinsIndependentlyOfCanonicalRouteOrder(): void
    {
        $parameter = new RouteDefinition('a-parameter', ['GET'], '/users/{id}');
        $literal = new RouteDefinition('z-literal', ['GET'], '/users/new');
        $match = (new RouteMatcher(new RouteTable([$parameter, $literal])))->match($this->request('GET', '/users/new'));

        self::assertNotNull($match);
        self::assertSame($literal, $match->route());
        self::assertSame([], $match->parameters());
    }

    public function testLexicographicSpecificityUsesEarlierSegmentsFirst(): void
    {
        $earlierLiteral = new RouteDefinition('z-earlier', ['GET'], '/a/{value}');
        $laterLiteral = new RouteDefinition('a-later', ['GET'], '/{value}/a');
        $match = (new RouteMatcher(new RouteTable([$laterLiteral, $earlierLiteral])))->match($this->request('GET', '/a/a'));

        self::assertNotNull($match);
        self::assertSame($earlierLiteral, $match->route());
        self::assertSame(['value' => 'a'], $match->parameters());
    }

    public function testMethodsPathsAndEncodedBytesAreComparedExactly(): void
    {
        $route = new RouteDefinition('route', ['GET'], '/files/{value}');
        $matcher = new RouteMatcher(new RouteTable([$route]));

        self::assertNull($matcher->match($this->request('get', '/files/value')));
        self::assertNull($matcher->match($this->request('GET', '/files/')));
        self::assertNull($matcher->match($this->request('GET', '/files/value/')));
        $upper = $matcher->match($this->request('GET', '/files/a%2Fb'));
        $lower = $matcher->match($this->request('GET', '/files/a%2fb'));
        self::assertSame('a%2Fb', $upper?->parameters()['value']);
        self::assertSame('a%2fb', $lower?->parameters()['value']);
    }

    public function testEmptyPathIsRootAndValidUnmatchedRequestsReturnNull(): void
    {
        $root = new RouteDefinition('root', ['GET'], '/');
        $matcher = new RouteMatcher(new RouteTable([$root]));

        self::assertSame($root, $matcher->match($this->request('GET', ''))?->route());
        self::assertNull($matcher->match($this->request('POST', '/')));
        self::assertNull($matcher->match($this->request('HEAD', '/')));
        self::assertNull($matcher->match($this->request('OPTIONS', '/')));
        self::assertNull($matcher->match($this->request('GET', '/other')));
    }

    public function testOnlyAuthorizedPsrGettersAreConsulted(): void
    {
        $route = new RouteDefinition('route', ['GET'], '/items/{id}');
        $matcher = new RouteMatcher(new RouteTable([$route]));
        $uri = $this->createMock(UriInterface::class);
        $uri->expects(self::once())->method('getPath')->willReturn('/items/42');
        $uri->expects(self::never())->method('getQuery');
        $uri->expects(self::never())->method('getFragment');
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getUri')->willReturn($uri);
        foreach (['getRequestTarget', 'getHeaders', 'getBody', 'getCookieParams', 'getQueryParams', 'getServerParams', 'getParsedBody', 'getUploadedFiles', 'getAttributes', 'getAttribute'] as $method) {
            $request->expects(self::never())->method($method);
        }

        $match = $matcher->match($request);
        self::assertSame(['id' => '42'], $match?->parameters());
    }

    public function testEquivalentReversedRouteSetsSelectEqualValues(): void
    {
        $routes = [
            new RouteDefinition('parameter', ['GET'], '/users/{id}'),
            new RouteDefinition('literal', ['GET'], '/users/new'),
            new RouteDefinition('other', ['POST'], '/users/{id}'),
        ];
        $forward = new RouteMatcher(new RouteTable($routes));
        $reverse = new RouteMatcher(new RouteTable(array_reverse($routes)));

        foreach ([['GET', '/users/new'], ['GET', '/users/42'], ['POST', '/users/42']] as [$method, $path]) {
            $forwardMatch = $forward->match($this->request($method, $path));
            $reverseMatch = $reverse->match($this->request($method, $path));
            self::assertSame($forwardMatch?->route()->name(), $reverseMatch?->route()->name());
            self::assertSame($forwardMatch?->parameters(), $reverseMatch?->parameters());
        }
    }

    public function testEverySuccessfulCallReturnsFreshMatchWithoutRetainingRequestState(): void
    {
        $matcher = new RouteMatcher(new RouteTable([new RouteDefinition('route', ['GET'], '/{id}')]));
        $request = $this->request('GET', '/value');
        $first = $matcher->match($request);
        $second = $matcher->match($request);

        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame($first->route(), $second->route());
        self::assertSame($first->parameters(), $second->parameters());
        self::assertSame(['routes', 'compiledRoutes'], array_map(
            static fn(\ReflectionProperty $property): string => $property->getName(),
            (new \ReflectionClass(RouteMatcher::class))->getProperties(),
        ));
    }

    public function testRequestValidationUsesExactDiagnosticVocabularies(): void
    {
        $matcher = new RouteMatcher(new RouteTable([]));
        foreach ([
            ['', '/', 'method', 'empty'],
            [str_repeat('A', 33), '/', 'method', 'too_long'],
            ['BAD METHOD', '/', 'method', 'syntax'],
            ['GET', str_repeat('a', 8193), 'path', 'too_long'],
            ['GET', 'relative', 'path', 'missing_leading_slash'],
            ['GET', '/' . implode('/', array_fill(0, 65, 'a')), 'path', 'too_many_segments'],
            ['GET', '/' . str_repeat('a', 1025), 'path', 'segment_too_long'],
            ['GET', '/%Q0', 'path', 'invalid_percent_triplet'],
            ['GET', '/bad path', 'path', 'invalid_path_byte'],
        ] as [$method, $path, $component, $violation]) {
            try {
                $matcher->match($this->request($method, $path));
                self::fail('Expected invalid request routing input.');
            } catch (InvalidRouteRequestException $exception) {
                self::assertSame(RoutingDiagnostic::InvalidRouteRequest->value, $exception->diagnosticId());
                self::assertSame($component, $exception->diagnosticContext()->fields()[0]->value());
                self::assertSame($violation, $exception->diagnosticContext()->fields()[2]->value());
            }
        }
    }

    public function testAcceptedRequestUpperBoundsAreStructurallyValid(): void
    {
        $matcher = new RouteMatcher(new RouteTable([]));
        $method = str_repeat('M', 32);
        $pathAtTotalBound = '/' . implode('/', array_fill(0, 8, str_repeat('a', 1023)));
        $pathAtSegmentCountBound = '/' . implode('/', array_fill(0, 64, 'a'));
        $pathAtSegmentLengthBound = '/' . str_repeat('a', 1024);

        self::assertSame(8192, strlen($pathAtTotalBound));
        self::assertNull($matcher->match($this->request($method, '/')));
        self::assertNull($matcher->match($this->request('GET', $pathAtTotalBound)));
        self::assertNull($matcher->match($this->request('GET', $pathAtSegmentCountBound)));
        self::assertNull($matcher->match($this->request('GET', $pathAtSegmentLengthBound)));
    }

    public function testGetterOrderAndThrowableIdentityArePreserved(): void
    {
        $matcher = new RouteMatcher(new RouteTable([]));
        $cause = new RuntimeException('method failure');
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willThrowException($cause);
        $request->expects(self::never())->method('getUri');
        try {
            $matcher->match($request);
            self::fail('Expected getter failure.');
        } catch (RuntimeException $exception) {
            self::assertSame($cause, $exception);
        }

        $uriCause = new RuntimeException('URI failure');
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getUri')->willThrowException($uriCause);
        try {
            $matcher->match($request);
            self::fail('Expected URI getter failure.');
        } catch (RuntimeException $exception) {
            self::assertSame($uriCause, $exception);
        }

        $pathCause = new RuntimeException('path failure');
        $uri = $this->createMock(UriInterface::class);
        $uri->expects(self::once())->method('getPath')->willThrowException($pathCause);
        $uri->expects(self::never())->method('getQuery');
        $uri->expects(self::never())->method('getFragment');
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getUri')->willReturn($uri);
        foreach (['getRequestTarget', 'getHeaders', 'getBody', 'getCookieParams', 'getQueryParams', 'getServerParams', 'getParsedBody', 'getUploadedFiles', 'getAttributes', 'getAttribute'] as $method) {
            $request->expects(self::never())->method($method);
        }
        try {
            $matcher->match($request);
            self::fail('Expected path getter failure.');
        } catch (RuntimeException $exception) {
            self::assertSame($pathCause, $exception);
        }
    }

    public function testRepeatedCallsRetainNoRequestSpecificState(): void
    {
        $first = new RouteDefinition('first', ['GET'], '/first/{id}');
        $second = new RouteDefinition('second', ['POST'], '/second/{id}');
        $matcher = new RouteMatcher(new RouteTable([$first, $second]));

        $firstMatch = $matcher->match($this->request('GET', '/first/one'));
        $secondMatch = $matcher->match($this->request('POST', '/second/two'));
        self::assertNotNull($firstMatch);
        self::assertNotNull($secondMatch);
        self::assertNotSame($firstMatch, $secondMatch);
        self::assertSame(['id' => 'one'], $firstMatch->parameters());
        self::assertSame(['id' => 'two'], $secondMatch->parameters());
        self::assertNull($matcher->match($this->request('GET', '/missing')));
        self::assertSame(['id' => 'one'], $firstMatch->parameters());
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
