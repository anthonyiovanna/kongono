<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Factory;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Factory\ResponseFactory;
use Kongono\Http\Message\Message\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;

final class ResponseFactoryTest extends TestCase
{
    public function testFactoryIsStatelessAndForwardsArguments(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse(201, 'Created');
        self::assertInstanceOf(ResponseFactoryInterface::class, $factory);
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(201, $response->getStatusCode());
        self::assertSame('Created', $response->getReasonPhrase());
        self::assertSame([], (new \ReflectionClass($factory))->getProperties());
    }

    public function testFactoryDefaultsAndFailureCategoryAreExact(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getReasonPhrase());
        self::assertSame('1.1', $response->getProtocolVersion());

        $this->expectException(InvalidMessageArgumentException::class);
        $factory->createResponse(99);
    }
}
