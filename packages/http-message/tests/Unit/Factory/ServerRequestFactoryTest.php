<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Factory;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Factory\ServerRequestFactory;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Message\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;

final class ServerRequestFactoryTest extends TestCase
{
    public function testFactoryIsStatelessAndUsesExplicitValues(): void
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/submit', ['A' => 1]);
        self::assertInstanceOf(ServerRequestFactoryInterface::class, $factory);
        self::assertInstanceOf(ServerRequest::class, $request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/submit', (string) $request->getUri());
        self::assertSame(['A' => 1], $request->getServerParams());
        self::assertSame([], (new \ReflectionClass($factory))->getProperties());
    }

    public function testFactoryForwardsUriObjectsAndUsesRequestDefaults(): void
    {
        $factory = new ServerRequestFactory();
        $uri = new Uri('https://example.test/path');
        $request = $factory->createServerRequest('GET', $uri);
        self::assertSame($uri, $request->getUri());
        self::assertSame('/path', $request->getRequestTarget());
        self::assertSame([], $request->getServerParams());
        self::assertSame([], $request->getCookieParams());
        self::assertSame([], $request->getQueryParams());
        self::assertSame([], $request->getUploadedFiles());
        self::assertNull($request->getParsedBody());
        self::assertSame([], $request->getAttributes());

        $this->expectException(InvalidMessageArgumentException::class);
        $factory->createServerRequest('bad method', '/');
    }
}
