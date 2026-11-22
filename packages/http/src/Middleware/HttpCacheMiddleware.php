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
 * Automatic HTTP Cache-Control, weak/strong ETag generation, and 304 Not Modified negotiation
 * for cacheable GET/HEAD responses.
 *
 * Security-hardened against CDN/shared-cache poisoning:
 *   - Never marks responses carrying `Set-Cookie` as `public` (forces `private, no-store`)
 *   - Never marks responses to `Authorization`-bearing requests as `public` (downgrades to `private` + `Vary: Authorization`)
 *   - Respects explicit `no-store` or `private` directives already set by the handler
 */
final readonly class HttpCacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private int $maxAge = 60,
        private bool $public = true,
        private ?int $staleWhileRevalidate = null,
        private bool $etag = true,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $method = strtoupper($request->getMethod());
        if (('GET' !== $method && 'HEAD' !== $method) || 200 !== $response->getStatusCode()) {
            return $response;
        }
        if ($response instanceof Response && $response->isStreaming()) {
            return $response;
        }

        $existingCc = strtolower($response->getHeaderLine('Cache-Control'));
        if (str_contains($existingCc, 'no-store')) {
            return $response;
        }

        // Never allow shared caches/CDNs to cache responses that emit session or auth cookies
        if ($response->hasHeader('Set-Cookie')) {
            return $response->withHeader('Cache-Control', 'private, no-store');
        }

        $hasAuthHeader = '' !== trim($request->getHeaderLine('Authorization'));
        $isPrivate = !$this->public || $hasAuthHeader || str_contains($existingCc, 'private');

        $directives = [$isPrivate ? 'private' : 'public', 'max-age=' . max(0, $this->maxAge)];
        if (!$isPrivate && null !== $this->staleWhileRevalidate && $this->staleWhileRevalidate > 0) {
            $directives[] = 'stale-while-revalidate=' . $this->staleWhileRevalidate;
        }
        $response = $response->withHeader('Cache-Control', implode(', ', $directives));

        if ($hasAuthHeader) {
            $vary = $response->getHeaderLine('Vary');
            if (!str_contains(strtolower($vary), 'authorization')) {
                $response = $response->withHeader('Vary', '' === $vary ? 'Authorization' : $vary . ', Authorization');
            }
        }

        if ($this->etag) {
            $etag = '' !== $response->getHeaderLine('ETag')
                ? $response->getHeaderLine('ETag')
                : 'W/"' . hash('xxh128', (string) $response->getBody()) . '"';
            $response = $response->withHeader('ETag', $etag);

            $ifNoneMatch = trim($request->getHeaderLine('If-None-Match'));
            if ('' !== $ifNoneMatch && $this->matchesEtag($ifNoneMatch, $etag)) {
                return $response->withStatus(304)->withBody(Stream::fromString(''));
            }
        }

        return $response;
    }

    private function matchesEtag(string $header, string $etag): bool
    {
        if ('*' === $header) {
            return true;
        }
        $normalizedTarget = ltrim($etag, 'W/');
        foreach (explode(',', $header) as $candidate) {
            if (ltrim(trim($candidate), 'W/') === $normalizedTarget) {
                return true;
            }
        }

        return false;
    }
}
