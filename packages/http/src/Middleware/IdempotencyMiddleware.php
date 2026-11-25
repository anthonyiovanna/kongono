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
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stripe-grade Idempotency-Key middleware for mutating HTTP requests (POST/PUT/PATCH).
 *
 * Prevents duplicate writes/charges on network retries:
 *   - Replays the exact cached status, safe headers, and body with `Idempotency-Replayed: true`
 *   - Fails closed with 409 Conflict if the same Idempotency-Key is reused with a different request payload
 *   - Prevents concurrent duplicate execution with an in-flight lock key
 */
final readonly class IdempotencyMiddleware implements MiddlewareInterface
{
    private const array MUTATING_METHODS = ['POST', 'PUT', 'PATCH'];

    /** @var list<string> */
    private const array SAFE_REPLAY_HEADERS = [
        'location',
        'etag',
        'vary',
        'content-language',
    ];

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
        $bodyStream = $request->getBody();
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
            $body = $bodyStream->getContents();
            $bodyStream->rewind();
        } else {
            $body = $bodyStream->getContents();
            $request = $request->withBody(Stream::fromString($body));
        }
        if ('' === $body) {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody) && [] !== $parsedBody) {
                $encodedParsed = json_encode($parsedBody, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                $body = is_string($encodedParsed) ? $encodedParsed : '';
            }
        }
        $filesDigest = $this->fingerprintUploadedFiles($request->getUploadedFiles());
        $reqContentType = strtolower(trim($request->getHeaderLine('Content-Type')));
        $path = self::canonicalizePath($request->getUri()->getPath());
        $query = $request->getUri()->getQuery();
        $fingerprint = hash('sha256', $method . '|' . $path . '|' . $query . '|' . $reqContentType . '|' . $body . '|' . $filesDigest);
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
            $replayHeaders = [
                'Content-Type' => $contentType,
            ];
            if (is_array($existing['headers'] ?? null)) {
                foreach ($existing['headers'] as $hName => $hVal) {
                    if (is_string($hName) && is_string($hVal)) {
                        $replayHeaders[$hName] = $hVal;
                    }
                }
            }
            $replayHeaders['Idempotency-Replayed'] = 'true';

            return new Response(
                statusCode: $status,
                headers: $replayHeaders,
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
            // Cache deterministic non-streaming, non-transient responses only (never cache 5xx, auth/rate-limit rejections, Set-Cookie, or no-store)
            $status = $response->getStatusCode();
            $cc = strtolower($response->getHeaderLine('Cache-Control'));
            $isStreaming = $response instanceof Response && $response->isStreaming();
            $isCacheable = !$isStreaming
                && $status < 500
                && !in_array($status, [401, 403, 408, 429], true)
                && !$response->hasHeader('Set-Cookie')
                && !str_contains($cc, 'no-store');
            if ($isCacheable) {
                $contentType = $response->getHeaderLine('Content-Type');
                $safeHeaders = [];
                foreach ($response->getHeaders() as $hName => $hVals) {
                    $lower = strtolower($hName);
                    if (in_array($lower, self::SAFE_REPLAY_HEADERS, true) || str_starts_with($lower, 'x-kongono-')) {
                        $safeHeaders[$hName] = implode(', ', $hVals);
                    }
                }
                $respBody = $response->getBody();
                if ($respBody->isSeekable()) {
                    $respBody->rewind();
                    $cachedBody = $respBody->getContents();
                    $respBody->rewind();
                } else {
                    $cachedBody = $respBody->getContents();
                    $response = $response->withBody(Stream::fromString($cachedBody));
                }
                $this->cache->set($cacheKey, [
                    'fingerprint' => $fingerprint,
                    'status' => $status,
                    'content_type' => '' !== $contentType ? $contentType : 'application/json; charset=utf-8',
                    'headers' => $safeHeaders,
                    'body' => $cachedBody,
                ], $this->ttlSeconds);
            }

            return $response;
        } finally {
            $this->cache->delete($lockKey);
        }
    }

    /** @param array<array-key, mixed> $uploadedFiles */
    private function fingerprintUploadedFiles(array $uploadedFiles): string
    {
        if ([] === $uploadedFiles) {
            return '';
        }

        $ctx = hash_init('sha256');
        $this->hashUploadedTree($ctx, '', $uploadedFiles);

        return hash_final($ctx);
    }

    /** @param array<array-key, mixed> $tree */
    private function hashUploadedTree(\HashContext $ctx, string $prefix, array $tree): void
    {
        ksort($tree, SORT_STRING);
        foreach ($tree as $key => $item) {
            $path = '' === $prefix ? (string) $key : $prefix . '.' . (string) $key;
            if ($item instanceof UploadedFileInterface) {
                $contentHash = '';
                if (UPLOAD_ERR_OK === $item->getError()) {
                    try {
                        $s = $item->getStream();
                        if ($s->isSeekable()) {
                            $s->rewind();
                            $fileCtx = hash_init('sha256');
                            while (!$s->eof()) {
                                $chunk = $s->read(65536);
                                if ('' === $chunk) {
                                    break;
                                }
                                hash_update($fileCtx, $chunk);
                            }
                            $s->rewind();
                            $contentHash = hash_final($fileCtx);
                        }
                    } catch (\Throwable) {
                        $contentHash = 'unreadable';
                    }
                }
                hash_update($ctx, sprintf(
                    '%s|%s|%s|%d|%d|%s;',
                    $path,
                    $item->getClientFilename() ?? '',
                    $item->getClientMediaType() ?? '',
                    $item->getSize() ?? -1,
                    $item->getError(),
                    $contentHash,
                ));
            } elseif (is_array($item)) {
                $this->hashUploadedTree($ctx, $path, $item);
            }
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
            $ctx = hash_init('sha256');
            foreach ($cookies as $k => $v) {
                $ks = (string) $k;
                $vs = is_scalar($v) ? (string) $v : '';
                hash_update($ctx, strlen($ks) . ':' . $ks . '=' . strlen($vs) . ':' . $vs . ';');
            }

            return 'cookie:' . hash_final($ctx);
        }

        $cookieHeader = trim($request->getHeaderLine('Cookie'));
        if ('' !== $cookieHeader) {
            return 'cookie_hdr:' . hash('sha256', $cookieHeader);
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
