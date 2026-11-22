<?php

declare(strict_types=1);

namespace Kongono\Jobs;

use Closure;

/**
 * Composable middleware wrapping job handler execution inside {@see Worker}.
 *
 * Ideal for cross-cutting concerns such as structured timing/logging, per-tenant RLS scoping,
 * rate limiting, circuit breakers, or custom retry policies.
 */
interface JobMiddlewareInterface
{
    /** @param Closure(Job): JobOutcome $next */
    public function handle(Job $job, Closure $next): JobOutcome;
}
