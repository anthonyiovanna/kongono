<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Factory;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Creates explicit Kongono server requests from supplied values only. */
#[Api(ApiStatus::Experimental)]
final class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /** @param array<array-key, mixed> $serverParams */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, $serverParams);
    }
}
