<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Kongono\Core\Container;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Contract for PHP 8.5 controller/method attributes that contribute PSR-15 middleware
 * to a route (e.g. #[Middleware(...)], #[Can('ability')], or custom app attributes).
 */
interface MiddlewareAttributeInterface
{
    public function toMiddleware(Container $container): MiddlewareInterface;
}
