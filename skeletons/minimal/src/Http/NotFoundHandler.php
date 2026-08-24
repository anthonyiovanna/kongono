<?php

declare(strict_types=1);

namespace App\Http;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Returns the application's exact unmatched response. */
final class NotFoundHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(404, ['Content-Type' => ['text/plain; charset=utf-8']], (new StreamFactory())->createStream("Not Found.\n"));
    }
}
