<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Contract;

use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\DependencyInjection\Exception\UnknownServiceException;
use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Resolves explicitly defined top-level service contracts at a composition root.
 *
 * The resolver contract is not itself a resolvable application service. Each
 * call and dependency edge is transient; implementations may perform their own
 * side effects and failures are propagated without a broad wrapping policy.
 */
#[Api(ApiStatus::Experimental)]
interface ResolverInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T> $contract
     *
     * @return T
     *
     * @throws UnknownServiceException
     * @throws InvalidDefinitionException if a constructed object violates the validated definition invariant
     * @throws \Throwable when registered autoloading or the resolved implementation constructor fails
     */
    public function resolve(string $contract): object;
}
