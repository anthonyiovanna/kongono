<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;
use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Container;
use Kongono\Core\CoreException;
use Kongono\Http\Middleware\IdempotencyMiddleware;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Declarative Idempotency-Key protection attribute for mutating controller actions.
 *
 * Example: `#[Idempotent(ttlSeconds: 86400)]`
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
final readonly class Idempotent implements MiddlewareAttributeInterface
{
    public function __construct(
        public int $ttlSeconds = 86400,
        public string $headerName = 'Idempotency-Key',
    ) {}

    public function toMiddleware(Container $container): MiddlewareInterface
    {
        if (!$container->has(CacheInterface::class)) {
            throw new CoreException(
                '#[Idempotent] requires a shared CacheInterface singleton bound in the container (refusing to fail open with an ephemeral per-request MemoryCache).',
            );
        }

        $cache = $container->make(CacheInterface::class);

        return new IdempotencyMiddleware($cache, $this->ttlSeconds, $this->headerName);
    }
}
