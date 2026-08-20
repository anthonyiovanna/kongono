<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Psr\Http\Message\ResponseInterface;

/** Constructs fresh fixed failure responses without consulting delivery data. */
#[Api(ApiStatus::Internal)]
final readonly class FailureResponseFactory
{
    private StreamFactory $streamFactory;

    public function __construct()
    {
        $this->streamFactory = new StreamFactory();
    }

    public function generic(): ResponseInterface
    {
        return new Response(500, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $this->streamFactory->createStream("Internal Server Error\n"));
    }

    public function trusted(string $document): ResponseInterface
    {
        return new Response(500, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'",
        ], $this->streamFactory->createStream($document));
    }
}
