<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Factory;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/** Creates explicit Kongono URI values. */
#[Api(ApiStatus::Experimental)]
final class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
