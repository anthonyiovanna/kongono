<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement\Tests\Fixture;

use Closure;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\SecurityEnforcement\ProtectedCapability;

/**
 * @template TResult
 * @implements ProtectedCapability<TResult>
 */
final readonly class CallbackProtectedCapability implements ProtectedCapability
{
    /** @param Closure(): TResult $callback */
    public function __construct(
        private Action $action,
        private ResourceReference $resource,
        private Closure $callback,
    ) {}

    public function action(): Action
    {
        return $this->action;
    }

    public function resource(): ResourceReference
    {
        return $this->resource;
    }

    /** @return TResult */
    public function execute(): mixed
    {
        return ($this->callback)();
    }
}
