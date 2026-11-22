<?php

declare(strict_types=1);

namespace Kongono\Http\Middleware;

use Closure;
use Kongono\Core\Cache\AtomicCacheInterface;
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

    /** @param (Closure(ServerRequestInterface): string)|null $scopeResolver Optional actor/tenant scope resolver */
    public function __construct(
        private CacheInterface $cache,
        private int $ttlSeconds = 86400,
        private string $headerName = 'Idempotency-Key',
        private string $cachePrefix = 'kongono:idemp:',
        private ?Closure $scopeResolver = null,
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

        $actorScope = $this->resolveActorScope($request);
        $body = (string) $request->getBody();
        if ('' === $body) {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody) && [] !== $parsedBody) {
                $encodedParsed = json_encode($parsedBody, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                $body = is_string($encodedParsed) ? $encodedParsed : '';
            }
        }
        $path = self::canonicalizePath($request->getUri()->getPath());
        $query = $request->getUri()->getQuery();
        $fingerprint = hash('sha256', $method . '|' . $path . '|' . $query . '|' . $body);
        $cacheKey = $this->cachePrefix . hash('sha256', $actorScope . '|' . $method . '|' . $path . '|' . $idempotencyKey);
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

        // Atomic in-flight lock prevents concurrent double-spend races
        if ($this->cache instanceof AtomicCacheInterface) {
            if (!$this->cache->add($lockKey, true, 60)) {
                throw new HttpException(409, 'A request with this Idempotency-Key is currently in progress.');
            }
        } else {
            if ($this->cache->has($lockKey)) {
                throw new HttpException(409, 'A request with this Idempotency-Key is currently in progress.');
            }
            $this->cache->set($lockKey, true, 60);
        }

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

    /**
     * Partition idempotency keys per authenticated actor (Principal, Bearer token, X-API-Key, or session cookie)
     * so two different users or tenants can NEVER replay or probe each other's responses.
     */
    private function resolveActorScope(ServerRequestInterface $request): string
    {
        if (null !== $this->scopeResolver) {
            return ($this->scopeResolver)($request);
        }

        $principal = $request->getAttribute('Kongono\\Security\\Principal');
        if (is_object($principal) && property_exists($principal, 'id') && is_string($principal->id) && '' !== $principal->id) {
            return 'principal:' . hash('sha256', $principal->id);
        }

        $auth = trim($request->getHeaderLine('Authorization'));
        if ('' !== $auth) {
            return 'auth:' . hash('sha256', $auth);
        }

        $apiKey = trim($request->getHeaderLine('X-API-Key'));
        if ('' !== $apiKey) {
            return 'apikey:' . hash('sha256', $apiKey);
        }

        $cookies = $request->getCookieParams();
        if ([] !== $cookies) {
            ksort($cookies, SORT_STRING);

            return 'cookie:' . hash('sha256', json_encode($cookies, JSON_THROW_ON_ERROR));
        }

        $server = $request->getServerParams();
        $ip = is_string($server['REMOTE_ADDR'] ?? null) ? $server['REMOTE_ADDR'] : 'local';

        return 'anon:' . hash('sha256', $ip);
    }

    private static function canonicalizePath(string $rawPath): string
    {
        $clean = '/' . trim($rawPath, '/');
        $collapsed = preg_replace('#/+#', '/', $clean);

        return is_string($collapsed) ? $collapsed : '/';
    }
}
