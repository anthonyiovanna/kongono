<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;
use Kongono\Core\Container;
use Kongono\Core\CoreException;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Attaches a PSR-15 middleware class to a controller or specific route method.
 * Resolved through the Kongono Container at dispatch time for full dependency injection.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Middleware implements MiddlewareAttributeInterface
{
    /** @param class-string<MiddlewareInterface> $middlewareClass */
    public function __construct(
        public string $middlewareClass,
    ) {}

    public function toMiddleware(Container $container): MiddlewareInterface
    {
        $resolved = $container->get($this->middlewareClass);
        if (!$resolved instanceof MiddlewareInterface) {
            throw new CoreException(sprintf('Attribute middleware "%s" did not resolve to a PSR-15 MiddlewareInterface.', $this->middlewareClass));
        }

        return $resolved;
    }
}
