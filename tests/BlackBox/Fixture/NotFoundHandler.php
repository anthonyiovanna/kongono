<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Returns the fixed unmatched fixture response. */
final class NotFoundHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(404, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], (new StreamFactory())->createStream("Not Found\n"));
    }
}
