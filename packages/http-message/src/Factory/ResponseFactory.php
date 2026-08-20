<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Factory;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/** Creates explicit Kongono server responses. */
#[Api(ApiStatus::Experimental)]
final class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, reasonPhrase: $reasonPhrase);
    }
}
