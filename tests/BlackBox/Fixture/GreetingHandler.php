<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Routing\Match\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/** Returns the one fixed successful fixture response for a valid greeting match. */
final class GreetingHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $match = $request->getAttribute(RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE);
        if (!$match instanceof RouteMatch || 'fixture.greeting' !== $match->route()->name()) {
            throw new RuntimeException('Fixture greeting route match is invalid.');
        }

        $parameters = $match->parameters();
        if (['name'] !== array_keys($parameters) || '' === $parameters['name']) {
            throw new RuntimeException('Fixture greeting parameter is invalid.');
        }

        $trace = $request->getAttribute(TraceMiddleware::TRACE_ATTRIBUTE);
        if (['outer-before', 'inner-before'] !== $trace) {
            throw new RuntimeException('Fixture greeting trace is invalid.');
        }

        return new Response(200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Kongono-Fixture-Trace' => [...$trace, 'handler'],
        ], (new StreamFactory())->createStream("Kongono Phase 2 fixture\n"));
    }
}
