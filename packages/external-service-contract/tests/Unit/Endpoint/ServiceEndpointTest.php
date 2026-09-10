<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Endpoint;

use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use PHPUnit\Framework\TestCase;

final class ServiceEndpointTest extends TestCase
{
    public function testEmptyAndCanonicalPathsAreAcceptedExactly(): void
    {
        self::assertTrue((new EndpointBasePath(''))->isEmpty());
        foreach (['/v1', '/v1/items', '/v1/%2F/item:one@scope', '/' . str_repeat('a', 1023)] as $path) {
            $base = new EndpointBasePath($path);
            self::assertSame($path, $base->value());
            self::assertTrue($base->equals(new EndpointBasePath($path)));
        }
        $endpoint = new ServiceEndpoint(new CanonicalOrigin(OriginScheme::Https, 'service.test', 443), new EndpointBasePath('/v1'));
        self::assertSame('service.test', $endpoint->origin()->host());
        self::assertTrue($endpoint->equals(new ServiceEndpoint(new CanonicalOrigin(OriginScheme::Https, 'service.test', 443), new EndpointBasePath('/v1'))));
    }

    public function testNoncanonicalPathsFailWithoutEchoingInput(): void
    {
        foreach (['/', 'relative', '//v1', '/v1/', '/./v1', '/../v1', '/v1?x', '/v1#x', '/v1\\x', '/v1/%2f', '/v1/%Q0', '/v1 x', '/' . str_repeat('a', 1024)] as $invalid) {
            try {
                new EndpointBasePath($invalid);
                self::fail('Expected endpoint path rejection.');
            } catch (InvalidExternalServiceContractException $exception) {
                self::assertStringNotContainsString($invalid, (string) $exception);
            }
        }
    }
}
