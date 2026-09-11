<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Factory;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Factory\RequestFactory;
use Kongono\Http\Message\Message\Request;
use Kongono\Http\Message\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;

final class RequestFactoryTest extends TestCase
{
    public function testFactoryIsStatelessAndCreatesOutboundRequests(): void
    {
        $factory = new RequestFactory();
        $uri = new Uri('https://example.test/path');
        $request = $factory->createRequest('POST', $uri);
        self::assertInstanceOf(RequestFactoryInterface::class, $factory);
        self::assertInstanceOf(Request::class, $request);
        self::assertSame($uri, $request->getUri());
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/path', $request->getRequestTarget());
        self::assertSame('example.test', $request->getHeaderLine('Host'));
        self::assertSame([], (new \ReflectionClass($factory))->getProperties());

        $this->expectException(InvalidMessageArgumentException::class);
        $factory->createRequest('bad method', '/');
    }
}
