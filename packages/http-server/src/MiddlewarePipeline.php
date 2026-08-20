<?php

declare(strict_types=1);

namespace Kongono\Http\Server;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException;
use Kongono\Http\Server\Internal\MiddlewareLayer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Immutable declaration-ordered PSR-15 pipeline compiled once at construction. */
#[Api(ApiStatus::Experimental)]
final readonly class MiddlewarePipeline implements RequestHandlerInterface
{
    public const int MAX_MIDDLEWARE = 128;

    /** @var list<MiddlewareInterface> */
    private array $middleware;
    private RequestHandlerInterface $terminalHandler;
    private RequestHandlerInterface $entryHandler;

    public function __construct(RequestHandlerInterface $terminalHandler, MiddlewareInterface ...$middleware)
    {
        if (count($middleware) > self::MAX_MIDDLEWARE) {
            throw new InvalidHttpServerDefinitionException(HttpServerDiagnostic::TooManyMiddleware, [
                'middleware_count' => count($middleware),
                'maximum_middleware' => self::MAX_MIDDLEWARE,
            ]);
        }

        $detached = [];
        foreach ($middleware as $component) {
            $detached[] = $component;
        }

        $this->middleware = $detached;
        $this->terminalHandler = $terminalHandler;
        $entryHandler = $terminalHandler;
        for ($position = count($detached) - 1; $position >= 0; --$position) {
            $entryHandler = new MiddlewareLayer($detached[$position], $entryHandler);
        }
        $this->entryHandler = $entryHandler;
    }

    /** @return list<MiddlewareInterface> */
    public function middleware(): array
    {
        return $this->middleware;
    }

    public function terminalHandler(): RequestHandlerInterface
    {
        return $this->terminalHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->entryHandler->handle($request);
    }
}
