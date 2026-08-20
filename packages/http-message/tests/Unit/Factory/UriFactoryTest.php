<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Factory;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Factory\UriFactory;
use Kongono\Http\Message\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;

final class UriFactoryTest extends TestCase
{
    public function testFactoryIsStatelessAndForwardsUri(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('HTTPS://Example.com/a');
        self::assertInstanceOf(UriFactoryInterface::class, $factory);
        self::assertInstanceOf(Uri::class, $uri);
        self::assertSame('https://example.com/a', (string) $uri);
        self::assertSame([], (new \ReflectionClass($factory))->getProperties());
    }

    public function testFactoryDefaultsAndFailureCategoryAreExact(): void
    {
        $factory = new UriFactory();
        self::assertSame('', (string) $factory->createUri());

        $this->expectException(InvalidMessageArgumentException::class);
        $factory->createUri('http://bad host/');
    }
}
