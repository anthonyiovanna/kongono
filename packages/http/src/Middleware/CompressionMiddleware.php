<?php

declare(strict_types=1);

namespace Kongono\Http\Middleware;

use Kongono\Http\Response;
use Kongono\Http\Session\Session;
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
            $session = $request->getAttribute(Session::class);
            $hasActiveSession = $session instanceof Session
                && ($session->isDirty() || $session->wasIdAccessed() || $session->wasRegenerated() || $session->isPersisted());
            $hasCreds = $hasActiveSession
                || '' !== trim($request->getHeaderLine('Authorization'))
                || '' !== trim($request->getHeaderLine('Proxy-Authorization'))
                || '' !== trim($request->getHeaderLine('X-API-Key'))
                || '' !== trim($request->getHeaderLine('Cookie'))
                || [] !== $request->getCookieParams()
                || null !== $request->getAttribute('Kongono\\Security\\Principal')
                || str_contains($cc, 'private');
            if ($hasCreds) {
                return $response;
            }
        }

        $encoding = $this->negotiateEncoding($request->getHeaderLine('Accept-Encoding'));
        if (null === $encoding) {
            // Advertise Accept-Encoding variance on cacheable compressible responses so CDNs never mix encodings
            return $this->withVaryAcceptEncoding($response);
        }

        $stream = $response->getBody();
        $seekable = $stream->isSeekable();
        $size = $stream->getSize();
        if (null !== $size && ($size < $this->minBytes || $size > $this->maxBytes)) {
            return $response;
        }
        // Never drain an unbounded non-seekable response stream
        if (!$seekable && null === $size) {
            return $response;
        }

        // Bounded streaming read so unknown-length streams cannot cause OOM before maxBytes is checked
        if ($seekable) {
            $stream->rewind();
        }
        $body = '';
        while (!$stream->eof()) {
            $chunk = $stream->read(min(65536, ($this->maxBytes - strlen($body)) + 1));
            if ('' === $chunk) {
                break;
            }
            $body .= $chunk;
            if (strlen($body) > $this->maxBytes) {
                if ($seekable) {
                    $stream->rewind();
                }

                return $response;
            }
        }
        $bodyLen = strlen($body);
        if ($bodyLen < $this->minBytes) {
            if ($seekable) {
                $stream->rewind();

                return $response;
            }

            return $response->withBody(Stream::fromString($body));
        }

        $compressed = 'gzip' === $encoding
            ? gzencode($body, $this->level)
            : gzcompress($body, $this->level);

        if (!is_string($compressed) || strlen($compressed) >= $bodyLen) {
            if ($seekable) {
                $stream->rewind();

                return $response;
            }

            return $response->withBody(Stream::fromString($body));
        }

        $out = $this->withVaryAcceptEncoding($response)
            ->withBody(Stream::fromString($compressed))
            ->withHeader('Content-Encoding', $encoding)
            ->withHeader('Content-Length', (string) strlen($compressed));

        // RFC 9110: weaken strong ETag when applying Content-Encoding so byte representations never collide
        $etag = trim($out->getHeaderLine('ETag'));
        if ('' !== $etag && !str_starts_with($etag, 'W/')) {
            $out = $out->withHeader('ETag', 'W/' . $etag);
        }

        return $out;
    }

    private function withVaryAcceptEncoding(ResponseInterface $response): ResponseInterface
    {
        $existingVary = trim($response->getHeaderLine('Vary'));
        $varyTokens = '' !== $existingVary ? array_map('trim', explode(',', $existingVary)) : [];
        $lowerTokens = array_map('strtolower', $varyTokens);
        if (!in_array('accept-encoding', $lowerTokens, true) && !in_array('*', $lowerTokens, true)) {
            $varyTokens[] = 'Accept-Encoding';
        }
        $vary = implode(', ', array_filter($varyTokens, static fn(string $t): bool => '' !== $t));

        return $response->withHeader('Vary', $vary);
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

        $gzipQ = $weights['gzip'] ?? ($weights['*'] ?? 0.0);
        $deflateQ = $weights['deflate'] ?? ($weights['*'] ?? 0.0);

        if ($gzipQ <= 0.0 && $deflateQ <= 0.0) {
            return null;
        }
        if ($deflateQ > $gzipQ && function_exists('gzcompress')) {
            return 'deflate';
        }
        if ($gzipQ > 0.0 && function_exists('gzencode')) {
            return 'gzip';
        }
        if ($deflateQ > 0.0 && function_exists('gzcompress')) {
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
