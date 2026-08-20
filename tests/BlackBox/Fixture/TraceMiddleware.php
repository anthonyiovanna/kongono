<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/** Records declaration-ordered trace tokens only in the current request/response. */
final readonly class TraceMiddleware implements MiddlewareInterface
{
    public const string TRACE_ATTRIBUTE = self::class . '.trace';

    public function __construct(private string $token)
    {
        if ('outer' !== $token && 'inner' !== $token) {
            throw new RuntimeException('Fixture trace token is invalid.');
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $trace = $request->getAttribute(self::TRACE_ATTRIBUTE);
        if (null === $trace) {
            $trace = [];
        }
        if (!is_array($trace) || !array_is_list($trace)) {
            throw new RuntimeException('Fixture request trace is invalid.');
        }
        foreach ($trace as $value) {
            if (!is_string($value) || ('outer-before' !== $value && 'inner-before' !== $value)) {
                throw new RuntimeException('Fixture request trace is invalid.');
            }
        }

        $trace[] = $this->token . '-before';
        $response = $handler->handle($request->withAttribute(self::TRACE_ATTRIBUTE, $trace));

        return $response->withAddedHeader('X-Kongono-Fixture-Trace', $this->token . '-after');
    }
}
