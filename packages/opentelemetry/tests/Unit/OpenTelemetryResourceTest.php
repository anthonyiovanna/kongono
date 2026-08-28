<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit;

use Kongono\OpenTelemetry\Exception\InvalidOpenTelemetryConfigurationException;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryResourceTest extends TestCase
{
    public function testExactAdmittedBytesAreRetained(): void
    {
        $resource = new OpenTelemetryResource('orders', '2.4.1', 'production');
        self::assertSame('orders', $resource->serviceName());
        self::assertSame('2.4.1', $resource->serviceVersion());
        self::assertSame('production', $resource->deploymentEnvironmentName());
    }

    /** @return iterable<string, array{string, ?string, ?string}> */
    public static function invalidResources(): iterable
    {
        yield 'empty name' => ['', null, null];
        yield 'long name' => [str_repeat('a', 129), null, null];
        yield 'control' => ["orders\n", null, null];
        yield 'invalid utf8' => ["\xFF", null, null];
        yield 'empty optional' => ['orders', '', null];
        yield 'long environment' => ['orders', null, str_repeat('a', 65)];
    }

    #[DataProvider('invalidResources')]
    public function testInvalidResourceFailsClosed(string $name, ?string $version, ?string $environment): void
    {
        $this->expectException(InvalidOpenTelemetryConfigurationException::class);
        new OpenTelemetryResource($name, $version, $environment);
    }
}
