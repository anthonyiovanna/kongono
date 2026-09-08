<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\Response;
use Psr\Http\Message\ResponseInterface;

/** Constructs the first-party response only after complete evidence validation. */
#[Api(ApiStatus::Internal)]
final class CurlResponseBuilder
{
    public function build(CurlResponseHeaderBlock $block, CurlResponseAccumulator $accumulator): ResponseInterface
    {
        return new Response(
            $block->statusCode(),
            $block->headers(),
            $accumulator->releaseBody(),
            '1.1',
            $block->reasonPhrase(),
        );
    }
}
