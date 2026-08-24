<?php

declare(strict_types=1);

namespace App\Http;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Returns the minimal application's single matched response. */
final class HomeHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, ['Content-Type' => ['text/plain; charset=utf-8']], (new StreamFactory())->createStream("Hello from Kongono.\n"));
    }
}
