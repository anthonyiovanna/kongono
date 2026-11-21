<?php

declare(strict_types=1);

namespace Kongono\Http\Middleware;

use Kongono\Core\Cache\CacheInterface;
use Kongono\Http\HttpException;
use Kongono\Http\Response;
use Kongono\Http\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Stripe-grade Idempotency-Key middleware for mutating HTTP requests (POST/PUT/PATCH).
 *
 * Prevents duplicate writes/charges on network retries:
 *   - Replays the exact cached status, content-type, and body with `Idempotency-Replayed: true`
 *   - Fails closed with 409 Conflict if the same Idempotency-Key is reused with a different request payload
 *   - Prevents concurrent duplicate execution with an in-flight lock key
 */
final readonly class IdempotencyMiddleware implements MiddlewareInterface
{
    private const array MUTATING_METHODS = ['POST', 'PUT', 'PATCH'];

    public function __construct(
        private CacheInterface $cache,
        private int $ttlSeconds = 86400,
        private string $headerName = 'Idempotency-Key',
        private string $cachePrefix = 'kongono:idemp:',
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        if (!in_array($method, self::MUTATING_METHODS, true)) {
            return $handler->handle($request);
        }

        $idempotencyKey = trim($request->getHeaderLine($this->headerName));
        if ('' === $idempotencyKey) {
            return $handler->handle($request);
        }
        if (strlen($idempotencyKey) > 128) {
            throw new HttpException(400, 'Idempotency-Key header must not exceed 128 characters.');
        }

        $body = (string) $request->getBody();
        $fingerprint = hash('sha256', $method . '|' . $request->getUri()->getPath() . '|' . $body);
        $cacheKey = $this->cachePrefix . hash('sha256', $idempotencyKey);
        $lockKey = $cacheKey . ':lock';

        $existing = $this->cache->get($cacheKey);
        if (is_array($existing)) {
            $storedFp = is_string($existing['fingerprint'] ?? null) ? $existing['fingerprint'] : '';
            if (!hash_equals($storedFp, $fingerprint)) {
                throw new HttpException(409, 'Idempotency-Key was already used with a different request payload.');
            }

            $status = is_int($existing['status'] ?? null) ? $existing['status'] : 200;
            $contentType = is_string($existing['content_type'] ?? null) ? $existing['content_type'] : 'application/json; charset=utf-8';
            $storedBody = is_string($existing['body'] ?? null) ? $existing['body'] : '';

            return new Response(
                statusCode: $status,
                headers: [
                    'Content-Type' => $contentType,
                    'Idempotency-Replayed' => 'true',
                ],
                body: Stream::fromString($storedBody),
            );
        }

        if ($this->cache->has($lockKey)) {
            throw new HttpException(409, 'A request with this Idempotency-Key is currently in progress.');
        }

        $this->cache->set($lockKey, true, 60);

        try {
            $response = $handler->handle($request);
            // Cache deterministic 2xx/4xx responses (do not cache 5xx transient server failures)
            if ($response->getStatusCode() < 500) {
                $contentType = $response->getHeaderLine('Content-Type');
                $this->cache->set($cacheKey, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'content_type' => '' !== $contentType ? $contentType : 'application/json; charset=utf-8',
                    'body' => (string) $response->getBody(),
                ], $this->ttlSeconds);
            }

            return $response;
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $this->cache->delete($lockKey);
        }
    }
}
