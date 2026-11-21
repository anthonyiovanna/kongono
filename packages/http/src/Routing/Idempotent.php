<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;
use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Cache\MemoryCache;
use Kongono\Core\Container;
use Kongono\Http\Middleware\IdempotencyMiddleware;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Declarative Idempotency-Key protection attribute for mutating controller actions.
 *
 * Example: `#[Idempotent(ttlSeconds: 86400)]`
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class Idempotent implements MiddlewareAttributeInterface
{
    public function __construct(
        public int $ttlSeconds = 86400,
        public string $headerName = 'Idempotency-Key',
    ) {}

    public function toMiddleware(Container $container): MiddlewareInterface
    {
        $cache = $container->has(CacheInterface::class)
            ? $container->make(CacheInterface::class)
            : new MemoryCache();

        return new IdempotencyMiddleware($cache, $this->ttlSeconds, $this->headerName);
    }
}
