<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;
use Kongono\Core\Container;
use Kongono\Http\Middleware\HttpCacheMiddleware;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Declarative HTTP caching & ETag/304 attribute for controller classes or GET methods.
 *
 * Example: `#[CacheControl(maxAge: 120, public: true, staleWhileRevalidate: 30)]`
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
final readonly class CacheControl implements MiddlewareAttributeInterface
{
    public function __construct(
        public int $maxAge = 60,
        public bool $public = true,
        public ?int $staleWhileRevalidate = null,
        public bool $etag = true,
    ) {}

    public function toMiddleware(Container $container): MiddlewareInterface
    {
        return new HttpCacheMiddleware($this->maxAge, $this->public, $this->staleWhileRevalidate, $this->etag);
    }
}
