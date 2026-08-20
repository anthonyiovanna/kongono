<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Immutable compiled link between one middleware component and its next handler. */
#[Api(ApiStatus::Internal)]
final readonly class MiddlewareLayer implements RequestHandlerInterface
{
    private MiddlewareInterface $middleware;
    private RequestHandlerInterface $nextHandler;

    public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $nextHandler)
    {
        $this->middleware = $middleware;
        $this->nextHandler = $nextHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->nextHandler);
    }
}
