<?php

declare(strict_types=1);

namespace Kongono\Http\Middleware;

use Kongono\Http\Response;
use Kongono\Http\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Automatic HTTP response compression (gzip / deflate via ext-zlib) with
 * safe skipping of streaming SSE, binary, tiny, or already-encoded responses.
 */
final readonly class CompressionMiddleware implements MiddlewareInterface
{
    private const array COMPRESSIBLE_PREFIXES = [
        'text/',
        'application/json',
        'application/javascript',
        'application/xml',
        'application/xhtml+xml',
        'image/svg+xml',
    ];

    public function __construct(
        private int $minBytes = 512,
        private int $level = 6,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response instanceof Response && $response->isStreaming()) {
            return $response;
        }
        if ('' !== $response->getHeaderLine('Content-Encoding')) {
            return $response;
        }

        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        if (str_contains($contentType, 'text/event-stream') || !$this->isCompressible($contentType)) {
            return $response;
        }

        $accept = strtolower($request->getHeaderLine('Accept-Encoding'));
        $encoding = null;
        if (str_contains($accept, 'gzip') && function_exists('gzencode')) {
            $encoding = 'gzip';
        } elseif (str_contains($accept, 'deflate') && function_exists('gzdeflate')) {
            $encoding = 'deflate';
        }
        if (null === $encoding) {
            return $response;
        }

        $body = (string) $response->getBody();
        if (strlen($body) < $this->minBytes) {
            return $response;
        }

        $compressed = 'gzip' === $encoding
            ? gzencode($body, $this->level)
            : gzdeflate($body, $this->level);

        if (!is_string($compressed) || strlen($compressed) >= strlen($body)) {
            return $response;
        }

        $existingVary = $response->getHeaderLine('Vary');
        $vary = '' === $existingVary
            ? 'Accept-Encoding'
            : (str_contains(strtolower($existingVary), 'accept-encoding') ? $existingVary : $existingVary . ', Accept-Encoding');

        return $response
            ->withBody(Stream::fromString($compressed))
            ->withHeader('Content-Encoding', $encoding)
            ->withHeader('Content-Length', (string) strlen($compressed))
            ->withHeader('Vary', $vary);
    }

    private function isCompressible(string $contentType): bool
    {
        foreach (self::COMPRESSIBLE_PREFIXES as $prefix) {
            if (str_starts_with($contentType, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
