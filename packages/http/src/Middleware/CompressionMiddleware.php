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
        private int $maxBytes = 10_485_760,
        private bool $compressAuthenticated = false,
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

        // Respect Cache-Control: no-transform and defend against BREACH (CVE-2013-3587) on secret-bearing responses
        $cc = strtolower($response->getHeaderLine('Cache-Control'));
        if (str_contains($cc, 'no-transform') || str_contains($cc, 'no-store') || $response->hasHeader('Set-Cookie')) {
            return $response;
        }

        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        if (str_contains($contentType, 'text/event-stream') || !$this->isCompressible($contentType)) {
            return $response;
        }

        if (!$this->compressAuthenticated) {
            $isDynamicDoc = str_starts_with($contentType, 'text/html') || str_starts_with($contentType, 'application/json');
            $hasCreds = '' !== trim($request->getHeaderLine('Authorization'))
                || '' !== trim($request->getHeaderLine('Cookie'))
                || [] !== $request->getCookieParams()
                || str_contains($cc, 'private');
            if ($isDynamicDoc && $hasCreds) {
                return $response;
            }
        }

        $encoding = $this->negotiateEncoding($request->getHeaderLine('Accept-Encoding'));
        if (null === $encoding) {
            return $response;
        }

        $stream = $response->getBody();
        $size = $stream->getSize();
        if (null !== $size && ($size < $this->minBytes || $size > $this->maxBytes)) {
            return $response;
        }

        $body = (string) $stream;
        $bodyLen = strlen($body);
        if ($bodyLen < $this->minBytes || $bodyLen > $this->maxBytes) {
            return $response;
        }

        $compressed = 'gzip' === $encoding
            ? gzencode($body, $this->level)
            : gzdeflate($body, $this->level);

        if (!is_string($compressed) || strlen($compressed) >= $bodyLen) {
            return $response;
        }

        $existingVary = $response->getHeaderLine('Vary');
        $vary = '' === $existingVary
            ? 'Accept-Encoding'
            : (str_contains(strtolower($existingVary), 'accept-encoding') ? $existingVary : $existingVary . ', Accept-Encoding');

        $out = $response
            ->withBody(Stream::fromString($compressed))
            ->withHeader('Content-Encoding', $encoding)
            ->withHeader('Content-Length', (string) strlen($compressed))
            ->withHeader('Vary', $vary);

        // RFC 9110: weaken strong ETag when applying Content-Encoding so byte representations never collide
        $etag = trim($out->getHeaderLine('ETag'));
        if ('' !== $etag && !str_starts_with($etag, 'W/')) {
            $out = $out->withHeader('ETag', 'W/' . $etag);
        }

        return $out;
    }

    private function negotiateEncoding(string $header): ?string
    {
        if ('' === trim($header)) {
            return null;
        }
        $weights = [];
        foreach (explode(',', strtolower($header)) as $part) {
            $segments = array_map('trim', explode(';', trim($part)));
            $name = $segments[0];
            if ('' === $name) {
                continue;
            }
            $q = 1.0;
            for ($i = 1, $len = count($segments); $i < $len; ++$i) {
                if (str_starts_with($segments[$i], 'q=')) {
                    $q = (float) substr($segments[$i], 2);
                }
            }
            $weights[$name] = $q;
        }

        if (($weights['gzip'] ?? 0.0) > 0.0 && function_exists('gzencode')) {
            return 'gzip';
        }
        if (($weights['deflate'] ?? 0.0) > 0.0 && function_exists('gzdeflate')) {
            return 'deflate';
        }

        return null;
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
